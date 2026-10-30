<?php

namespace App\Services;

use App\Models\Device;
use App\Models\DeviceLoan;
use App\Models\DeviceLoanUnit;
use App\Models\DeviceReservation;
use App\Models\User;
use App\Support\Audit;
use App\Support\Settings;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Ausleihen, Rückgaben (auch teilweise), Verlängerungen und das Umwandeln
 * von Vormerkungen. Alle Bestandsänderungen laufen in einer Transaktion mit
 * Zeilensperre auf dem Gerät, damit parallele Buchungen nicht überbuchen.
 */
class LoanService
{
    public function __construct(
        private Availability $availability,
        private Settings $settings,
        private CheckoutScans $checkoutScans,
    ) {}

    /**
     * @param array{
     *     borrower_name: string,
     *     loan_start_date: string,
     *     loan_end_date: string,
     *     loan_purpose?: string|null,
     *     notes?: string|null,
     *     quantity?: int|null,
     *     units?: list<int>|null,
     *     accessories?: array<int|string, bool>|null,
     * } $data
     */
    public function loan(Device $device, array $data, User $actor, ?DeviceReservation $fromReservation = null): DeviceLoan
    {
        return DB::transaction(function () use ($device, $data, $actor, $fromReservation) {
            /** @var Device $device */
            $device = Device::query()->whereKey($device->id)->lockForUpdate()->firstOrFail();

            // Status unter Sperre erneut prüfen – sonst wird eine Vormerkung bei zwei
            // gleichzeitigen Klicks zweimal ausgegeben
            if ($fromReservation) {
                $fromReservation = DeviceReservation::query()->whereKey($fromReservation->id)->lockForUpdate()->firstOrFail();
                if (! $fromReservation->isActive()) {
                    throw ValidationException::withMessages(['reservation' => __('Diese Vormerkung wurde bereits ausgegeben oder widerrufen.')]);
                }
            }

            $start = Carbon::parse($data['loan_start_date'])->startOfDay();
            $end = Carbon::parse($data['loan_end_date'])->endOfDay();
            $this->assertDuration($start, $end, 'loan_end_date');

            $units = $this->normalizeUnits($data['units'] ?? []);
            $quantity = $units ? count($units) : (int) ($data['quantity'] ?? 1);

            if ($quantity < 1) {
                throw ValidationException::withMessages(['quantity' => __('Die Menge muss mindestens 1 sein.')]);
            }
            if ($fromReservation && $units && count($units) !== $fromReservation->quantity) {
                throw ValidationException::withMessages(['units' => trans_choice(
                    'Bitte genau :count Exemplar auswählen (vorgemerkte Menge).|Bitte genau :count Exemplare auswählen (vorgemerkte Menge).',
                    $fromReservation->quantity, ['count' => $fromReservation->quantity],
                )]);
            }
            if ($this->settings->get('loans.units_required') && $device->total_quantity > 1 && ! $units) {
                throw ValidationException::withMessages(['units' => __('Bitte die Exemplar-Nummern angeben.')]);
            }

            $this->assertUnitsFree($device, $units);

            $from = $start->isToday() ? now() : $start;
            $free = $this->availability->free($device, $from, $end, null, $fromReservation?->id);
            if ($quantity > $free) {
                throw ValidationException::withMessages(['quantity' => $free <= 0
                    ? __('Für diesen Zeitraum ist kein Exemplar mehr frei (Ausleihen/Vormerkungen).')
                    : trans_choice('Für diesen Zeitraum ist nur noch :count Exemplar frei.|Für diesen Zeitraum sind nur noch :count Exemplare frei.', $free, ['count' => $free]),
                ]);
            }

            $loan = DeviceLoan::create([
                'device_id' => $device->id,
                'borrower_name' => $data['borrower_name'],
                'quantity' => $quantity,
                'returned_quantity' => 0,
                'loan_start_date' => $start->toDateString(),
                'loan_end_date' => $end->toDateString(),
                'loan_purpose' => $data['loan_purpose'] ?? null,
                'notes' => $data['notes'] ?? null,
                'accessories' => $this->accessorySnapshot($device, $quantity, $data['accessories'] ?? null),
                'loaned_by' => $actor->id,
                // Vorher gescannt? Dann geht es am Kiosk auch nur per Scan zurück
                'checkout_scanned' => $this->checkoutScans->scanned($device),
                'device_reservation_id' => $fromReservation?->id,
            ]);

            foreach ($units as $unit) {
                DeviceLoanUnit::create(['device_loan_id' => $loan->id, 'device_id' => $device->id, 'unit_no' => $unit]);
            }

            if ($fromReservation) {
                $fromReservation->update(['status' => DeviceReservation::STATUS_CONVERTED]);
            }

            Audit::log($fromReservation ? 'loan.converted' : 'loan.created', $loan->setRelation('device', $device), array_filter([
                'borrower' => $loan->borrower_name,
                'quantity' => $quantity,
                'units' => $units ? array_map(fn ($u) => $device->unitLabel($u), $units) : null,
                'until' => $end->format('d.m.Y'),
            ]), $actor);

            return $loan;
        });
    }

    /**
     * Rückgabe – komplett oder teilweise (Menge oder bestimmte Exemplare).
     *
     * @param  list<int>|null  $units
     * @param  array<int|string, bool>|null  $accessoryChecks  Index → vollständig zurück?
     */
    public function return(
        DeviceLoan $loan,
        User $actor,
        ?int $quantity = null,
        ?array $units = null,
        ?array $accessoryChecks = null,
        ?string $note = null,
    ): DeviceLoan {
        return DB::transaction(function () use ($loan, $actor, $quantity, $units, $accessoryChecks, $note) {
            Device::query()->whereKey($loan->device_id)->lockForUpdate()->first();
            /** @var DeviceLoan $loan */
            $loan = DeviceLoan::query()->with('units', 'device')->whereKey($loan->id)->lockForUpdate()->firstOrFail();

            if (! $loan->isActive()) {
                throw ValidationException::withMessages(['quantity' => __('Diese Ausleihe ist bereits abgeschlossen.')]);
            }

            $outstandingUnits = $loan->outstandingUnitNumbers();
            $units = $this->normalizeUnits($units ?? []);

            if ($units) {
                $unknown = array_diff($units, $outstandingUnits);
                if ($unknown) {
                    throw ValidationException::withMessages(['units' => __('Exemplar :units gehört nicht zu dieser Ausleihe.', [
                        'units' => implode(', ', array_map(fn ($u) => $loan->device->unitLabel($u), $unknown)),
                    ])]);
                }
                $quantity = count($units);
            }

            $quantity ??= $loan->outstanding_quantity;
            if ($quantity < 1 || $quantity > $loan->outstanding_quantity) {
                throw ValidationException::withMessages(['quantity' => __('Ungültige Rückgabemenge (offen: :n).', ['n' => $loan->outstanding_quantity])]);
            }

            // Mit Exemplar-Nummern verliehen: Teilrückgaben nur mit Angabe der Exemplare,
            // sonst weiß niemand mehr, welche Nummern noch draußen sind
            if ($outstandingUnits && ! $units && $quantity < $loan->outstanding_quantity) {
                throw ValidationException::withMessages(['units' => __('Bitte die zurückgegebenen Exemplare auswählen.')]);
            }

            $loan->returned_quantity += $quantity;
            $fullyReturned = $loan->quantity - $loan->returned_quantity <= 0;

            // Bei Komplettrückgabe alle noch offenen Exemplare schließen (auch Altfälle,
            // in denen Menge und Exemplar-Liste nicht übereinstimmen)
            if ($fullyReturned) {
                $units = $units ?: $outstandingUnits;
                DeviceLoanUnit::query()->where('device_loan_id', $loan->id)
                    ->whereNull('returned_at')
                    ->update(['returned_at' => now()]);
            } elseif ($units) {
                DeviceLoanUnit::query()->where('device_loan_id', $loan->id)
                    ->whereIn('unit_no', $units)->whereNull('returned_at')
                    ->update(['returned_at' => now()]);
            }

            $issues = null;
            if ($fullyReturned) {
                $loan->returned_at = now();
                $loan->returned_by = $actor->id;
                $issues = $this->missingAccessories($loan, $accessoryChecks);
                if ($issues) {
                    $loan->return_issues = $issues;
                }
            }

            if ($note) {
                $loan->notes = trim(($loan->notes ? $loan->notes."\n" : '').now()->format('d.m.Y').': '.$note);
            }

            $loan->save();

            Audit::log($fullyReturned ? 'loan.returned' : 'loan.partially_returned', $loan, array_filter([
                'borrower' => $loan->borrower_name,
                'quantity' => $quantity,
                'units' => $units ? array_map(fn ($u) => $loan->device->unitLabel($u), $units) : null,
                'remaining' => $loan->outstanding_quantity ?: null,
                'missing' => $issues ? array_map(fn ($i) => $i['missing'].'× '.$i['name'], $issues) : null,
                'note' => $note,
            ]), $actor);

            return $loan;
        });
    }

    public function extend(DeviceLoan $loan, string $newEndDate, User $actor): DeviceLoan
    {
        return DB::transaction(function () use ($loan, $newEndDate, $actor) {
            $device = Device::query()->whereKey($loan->device_id)->lockForUpdate()->firstOrFail();
            $loan->refresh();

            if (! $loan->isActive()) {
                throw ValidationException::withMessages(['loan_end_date' => __('Diese Ausleihe ist bereits abgeschlossen.')]);
            }

            $oldEnd = $loan->loan_end_date->copy();
            $newEnd = Carbon::parse($newEndDate)->endOfDay();

            if ($newEnd->lte($oldEnd->copy()->endOfDay())) {
                throw ValidationException::withMessages(['loan_end_date' => __('Das neue Rückgabedatum muss nach dem bisherigen liegen.')]);
            }
            $this->assertDuration($loan->loan_start_date->copy()->startOfDay(), $newEnd, 'loan_end_date');

            // Im Verlängerungszeitraum muss die eigene Menge weiterhin frei sein
            $from = $oldEnd->copy()->addDay()->startOfDay();
            if ($from->isPast()) {
                $from = now();
            }
            $free = $this->availability->free($device, $from, $newEnd, $loan->id);
            if ($free < $loan->outstanding_quantity) {
                throw ValidationException::withMessages(['loan_end_date' => __('Verlängerung nicht möglich: Im Zeitraum sind Exemplare vorgemerkt bzw. verliehen (frei: :n).', ['n' => $free])]);
            }

            $loan->loan_end_date = $newEnd;
            $loan->extension_count++;
            $loan->save();

            Audit::log('loan.extended', $loan->setRelation('device', $device), [
                'borrower' => $loan->borrower_name,
                'from' => $oldEnd->format('d.m.Y'),
                'until' => $newEnd->format('d.m.Y'),
            ], $actor);

            return $loan;
        });
    }

    public function resolveIssues(DeviceLoan $loan, User $actor, ?string $note = null): void
    {
        $loan->update([
            'issues_resolved_at' => now(),
            'issues_resolved_by' => $actor->id,
            'notes' => $note ? trim(($loan->notes ? $loan->notes."\n" : '').now()->format('d.m.Y').': '.$note) : $loan->notes,
        ]);

        Audit::log('loan.issues_resolved', $loan, array_filter(['note' => $note]), $actor);
    }

    /**
     * Vormerkung → Ausleihe (übernimmt Menge, Zeitraum-Ende, Zweck, Name).
     */
    public function convertReservation(DeviceReservation $reservation, User $actor, ?string $borrowerName = null, ?array $units = null, ?array $accessories = null): DeviceLoan
    {
        $leadHours = (int) $this->settings->get('loans.convert_lead_hours');
        if ($reservation->start_at->copy()->subHours($leadHours)->isFuture()) {
            throw ValidationException::withMessages(['reservation' => __('Diese Vormerkung kann erst ab :date umgewandelt werden.', [
                'date' => $reservation->start_at->copy()->subHours($leadHours)->format('d.m.Y H:i'),
            ])]);
        }

        $end = $reservation->end_at->isPast() ? today() : $reservation->end_at;

        return $this->loan($reservation->device, [
            'borrower_name' => $borrowerName ?: $reservation->displayName(),
            'loan_start_date' => today()->toDateString(),
            'loan_end_date' => $end->toDateString(),
            'loan_purpose' => $reservation->purpose,
            'quantity' => $reservation->quantity,
            'units' => $units,
            'accessories' => $accessories,
        ], $actor, $reservation);
    }

    /**
     * @param  list<int>  $units
     */
    private function assertUnitsFree(Device $device, array $units): void
    {
        if (! $units) {
            return;
        }

        $invalid = array_filter($units, fn ($u) => $u < 1 || $u > $device->total_quantity);
        if ($invalid) {
            throw ValidationException::withMessages(['units' => __('Exemplar :units existiert nicht (dieses Gerät hat :n Exemplare).', [
                'units' => implode(', ', array_map(fn ($u) => $device->unitLabel($u), $invalid)),
                'n' => $device->total_quantity,
            ])]);
        }

        $busy = $device->loanedUnits();
        $conflicts = array_intersect($units, array_keys($busy));
        if ($conflicts) {
            $messages = array_map(fn ($u) => __('Exemplar :unit ist bereits an :name verliehen.', [
                'unit' => $device->unitLabel($u),
                'name' => $busy[$u]->borrower_name,
            ]), $conflicts);

            throw ValidationException::withMessages(['units' => implode(' ', $messages)]);
        }
    }

    private function assertDuration(Carbon $start, Carbon $end, string $field): void
    {
        if ($end->lt($start)) {
            throw ValidationException::withMessages([$field => __('Das Rückgabedatum muss nach dem Ausleihdatum liegen.')]);
        }

        $max = (int) $this->settings->get('loans.max_days');
        if ($max > 0 && $start->copy()->startOfDay()->diffInDays($end->copy()->startOfDay()) > $max) {
            throw ValidationException::withMessages([$field => __('Maximale Ausleihdauer: :n Tage.', ['n' => $max])]);
        }
    }

    /**
     * @param  list<int|string>  $units
     * @return list<int>
     */
    private function normalizeUnits(array $units): array
    {
        $units = array_map('intval', array_filter($units, fn ($u) => $u !== null && $u !== ''));
        $units = array_values(array_unique($units));
        sort($units);

        return $units;
    }

    /**
     * Zubehör-Stand bei Ausgabe festhalten (Menge je Exemplar × Ausleihmenge).
     *
     * @param  array<int|string, bool>|null  $checks  Zubehör-ID → mitgegeben?
     * @return list<array{name: string, quantity: int, checked: bool}>|null
     */
    private function accessorySnapshot(Device $device, int $quantity, ?array $checks): ?array
    {
        $accessories = $device->accessories()->get();
        if ($accessories->isEmpty()) {
            return null;
        }

        return $accessories->map(fn ($a) => [
            'name' => $a->name,
            'quantity' => $a->quantity * $quantity,
            'checked' => $checks === null ? true : (bool) ($checks[$a->id] ?? false),
        ])->values()->all();
    }

    /**
     * Fehlendes Zubehör bei Rückgabe ermitteln. Es zählt nur Zubehör, das bei
     * der Ausgabe auch mitgegeben wurde.
     *
     * @param  array<int|string, bool>|null  $checks  Index im Ausgabe-Snapshot → zurück?
     * @return list<array{name: string, missing: int}>|null
     */
    private function missingAccessories(DeviceLoan $loan, ?array $checks): ?array
    {
        $given = collect($loan->accessories ?? [])->filter(fn ($a) => $a['checked'] ?? true);
        if ($given->isEmpty()) {
            return null;
        }

        if ($checks === null) {
            if ($this->settings->get('loans.accessories_check_required')) {
                throw ValidationException::withMessages(['accessories' => __('Bitte die Zubehör-Checkliste ausfüllen.')]);
            }

            return null;
        }

        $missing = [];
        foreach ($given as $index => $item) {
            if (empty($checks[$index])) {
                $missing[] = ['name' => $item['name'], 'missing' => (int) $item['quantity']];
            }
        }

        return $missing ?: null;
    }
}
