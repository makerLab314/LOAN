<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\DeviceLoan;
use App\Models\DeviceLoanUnit;
use App\Services\LoanService;
use App\Support\Settings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Ausleihe per Scan: ein oder mehrere Geräte (auch mehrere Exemplare) sammeln
 * und mit einem Namen und Zeitraum auf einmal ausleihen. Alles oder nichts –
 * scheitert ein Gerät, wird keine der Ausleihen gebucht.
 */
class LoanCheckoutController extends Controller
{
    public const MAX_ITEMS = 50;

    public function __construct(
        private LoanService $loans,
        private Settings $settings,
    ) {}

    public function create(Request $request): View
    {
        $devices = Device::query()
            ->withLoanedQuantity()
            ->with('accessories')
            ->orderBy('title')
            ->get();

        $busy = DeviceLoanUnit::query()->whereNull('returned_at')->get(['device_id', 'unit_no'])
            ->groupBy('device_id')->map(fn ($units) => $units->pluck('unit_no')->map(fn ($n) => (int) $n)->values());

        // Für die Seite nur, was zum Anzeigen nötig ist – keine Namen von Ausleihenden
        $catalog = $devices->map(fn (Device $d) => [
            'id' => $d->id,
            'title' => $d->title,
            'label' => $d->inventory_number ?? 'ID'.$d->id,
            'total' => $d->total_quantity,
            'available' => $d->available_quantity,
            'busy' => $busy->get($d->id, collect())->all(),
            'accessories' => $d->accessories->map(fn ($a) => $a->quantity.'× '.$a->name)->all(),
        ])->values();

        // Nach Validierungsfehlern die Auswahl wiederherstellen
        $initial = collect((array) old('items', []))
            ->filter(fn ($item) => is_array($item) && isset($item['device_id']))
            ->map(fn ($item) => [
                'id' => (int) $item['device_id'],
                'units' => array_values(array_map('intval', array_filter((array) ($item['units'] ?? []), 'is_numeric'))),
                'quantity' => max(1, (int) ($item['quantity'] ?? 1)),
            ])
            ->take(self::MAX_ITEMS)
            ->values();

        return view('loans.checkout', [
            'devices' => $devices->filter(fn (Device $d) => $d->available_quantity > 0)->values(),
            'catalog' => $catalog,
            'initial' => $initial,
            'unitsRequired' => (bool) $this->settings->get('loans.units_required'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'borrower_name' => ['required', 'string', 'max:255'],
            'loan_start_date' => ['required', 'date', 'after_or_equal:'.today()->subDays(7)->toDateString()],
            'loan_end_date' => ['required', 'date', 'after_or_equal:loan_start_date'],
            'loan_purpose' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'accessories_confirmed' => ['nullable', 'boolean'],
            'items' => ['required', 'array', 'min:1', 'max:'.self::MAX_ITEMS],
            'items.*' => ['array'],
            'items.*.device_id' => ['required', 'integer', Rule::exists('devices', 'id')->whereNull('deleted_at')],
            'items.*.quantity' => ['nullable', 'integer', 'min:1', 'max:9999'],
            'items.*.units' => ['nullable', 'array', 'max:9999'],
            'items.*.units.*' => ['integer', 'min:1', 'max:9999'],
        ], [
            'items.required' => __('Bitte mindestens ein Gerät scannen oder auswählen.'),
        ], [
            'borrower_name' => __('Ausleihende Person'),
            'loan_start_date' => __('Ausleihdatum'),
            'loan_end_date' => __('Rückgabedatum'),
            'loan_purpose' => __('Zweck'),
            'items' => __('Geräte'),
        ]);

        // Zeilen je Gerät zusammenfassen (gleiches Gerät mehrfach gescannt)
        $lines = [];
        foreach ($data['items'] as $item) {
            $id = (int) $item['device_id'];
            $lines[$id] ??= ['units' => [], 'quantity' => 0];
            if (! empty($item['units'])) {
                $lines[$id]['units'] = array_values(array_unique(array_merge($lines[$id]['units'], array_map('intval', $item['units']))));
            } else {
                $lines[$id]['quantity'] += (int) ($item['quantity'] ?? 1);
            }
        }
        // Feste Reihenfolge der Zeilensperren – zwei gleichzeitige Ausleihen blockieren sich nicht gegenseitig
        ksort($lines);

        $devices = Device::query()->whereKey(array_keys($lines))->withCount('accessories')->get()->keyBy('id');

        foreach ($lines as $id => $line) {
            if ($line['units'] && $line['quantity']) {
                throw ValidationException::withMessages(['items' => __(':title: Bitte entweder Exemplare oder eine Menge angeben.', ['title' => $devices[$id]->title])]);
            }
        }

        if ($devices->contains(fn (Device $d) => $d->accessories_count > 0) && ! $request->boolean('accessories_confirmed')) {
            throw ValidationException::withMessages(['accessories_confirmed' => __('Bitte bestätigen, dass das Zubehör mitgegeben wurde.')]);
        }

        $created = DB::transaction(function () use ($lines, $devices, $data, $request) {
            $created = [];
            foreach ($lines as $id => $line) {
                $device = $devices[$id];
                try {
                    $created[] = $this->loans->loan($device, [
                        'borrower_name' => $data['borrower_name'],
                        'loan_start_date' => $data['loan_start_date'],
                        'loan_end_date' => $data['loan_end_date'],
                        'loan_purpose' => $data['loan_purpose'] ?? null,
                        'notes' => $data['notes'] ?? null,
                        'quantity' => $line['units'] ? null : max(1, $line['quantity']),
                        'units' => $line['units'] ?: null,
                    ], $request->user());
                } catch (ValidationException $e) {
                    // Zeitraum-Fehler betreffen alle Geräte, alles andere einem Gerät zuordnen
                    if (isset($e->errors()['loan_end_date'])) {
                        throw $e;
                    }
                    throw ValidationException::withMessages(['items' => $device->title.' ('.($device->inventory_number ?? 'ID'.$device->id).'): '.collect($e->errors())->flatten()->first()]);
                }
            }

            return $created;
        });

        $count = collect($created)->sum(fn (DeviceLoan $l) => $l->quantity);

        return redirect()->route('loans.index')->with('success', trans_choice(
            ':count Exemplar an :name verliehen.|:count Exemplare an :name verliehen.',
            $count,
            ['count' => $count, 'name' => $data['borrower_name']],
        ));
    }
}
