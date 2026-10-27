<?php

namespace App\Services;

use App\Models\Device;
use App\Models\DeviceReservation;
use App\Models\Room;
use App\Models\RoomBooking;
use App\Models\User;
use App\Support\Audit;
use App\Support\Settings;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Raumbuchungen und Geräte-Vormerkungen: Zeitraum- und Überschneidungsprüfung.
 */
class BookingRules
{
    public function __construct(
        private Availability $availability,
        private Settings $settings,
    ) {}

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    public function period(string $startDate, string $startTime, string $endDate, string $endTime): array
    {
        $start = Carbon::createFromFormat('Y-m-d H:i', $startDate.' '.$startTime);
        $end = Carbon::createFromFormat('Y-m-d H:i', $endDate.' '.$endTime);

        if (! $start || ! $end) {
            throw ValidationException::withMessages(['start_date' => __('Ungültiges Datum.')]);
        }

        if ($end->lte($start)) {
            throw ValidationException::withMessages(['end_time' => __('Das Ende muss nach dem Beginn liegen.')]);
        }

        return [$start->seconds(0), $end->seconds(0)];
    }

    /**
     * Beginn und Höchstdauer prüfen – ohne Blick auf andere Buchungen.
     */
    public function assertRoomPeriod(Carbon $start, Carbon $end, ?RoomBooking $existing = null): void
    {
        $this->assertNotPast($start, $existing?->start_at);

        $maxHours = (int) $this->settings->get('rooms.max_hours');
        if ($maxHours > 0 && $start->diffInMinutes($end) > $maxHours * 60) {
            throw ValidationException::withMessages(['end_time' => __('Maximale Buchungsdauer: :n Stunden.', ['n' => $maxHours])]);
        }
    }

    /**
     * Buchungen des Raums, die sich mit dem Zeitraum überschneiden.
     *
     * @return Collection<int, RoomBooking>
     */
    public function roomConflicts(Room $room, Carbon $start, Carbon $end, ?int $ignoreId = null): Collection
    {
        return RoomBooking::query()
            ->with('user')
            ->where('room_id', $room->id)
            ->overlapping($start, $end)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->orderBy('start_at')
            ->get();
    }

    /**
     * @param  Collection<int, RoomBooking>  $conflicts
     */
    public function conflictMessage(Collection $conflicts): string
    {
        $describe = fn (RoomBooking $b) => $b->start_at->format('d.m.').' '.$b->timeRange()
            .' ('.collect([$b->purpose, $b->displayName()])->filter()->implode(', ').')';

        if ($conflicts->count() === 1) {
            return __('Der Raum ist in diesem Zeitraum bereits gebucht: :booking.', ['booking' => $describe($conflicts->first())]);
        }

        $list = $conflicts->take(5)->map($describe)->implode('; ').($conflicts->count() > 5 ? '; …' : '');

        return __('Der Raum ist in diesem Zeitraum :count-mal belegt: :list.', ['count' => $conflicts->count(), 'list' => $list]);
    }

    public function bookRoom(Room $room, Carbon $start, Carbon $end, ?string $purpose, User $actor, ?RoomBooking $existing = null, ?string $bookedFor = null): RoomBooking
    {
        $this->assertRoomPeriod($start, $end, $existing);

        return DB::transaction(function () use ($room, $start, $end, $purpose, $actor, $existing, $bookedFor) {
            Room::query()->whereKey($room->id)->lockForUpdate()->first();

            $conflicts = $this->roomConflicts($room, $start, $end, $existing?->id);
            if ($conflicts->isNotEmpty()) {
                throw ValidationException::withMessages(['start_date' => $this->conflictMessage($conflicts)]);
            }

            if ($existing) {
                $before = ['start' => $existing->start_at->format('d.m.Y H:i'), 'end' => $existing->end_at->format('d.m.Y H:i')];
                $existing->update([
                    'room_id' => $room->id, 'start_at' => $start, 'end_at' => $end,
                    'purpose' => $purpose, 'booked_by_name' => $bookedFor,
                ]);
                Audit::log('room_booking.updated', $existing, ['before' => $before, 'after' => [
                    'start' => $start->format('d.m.Y H:i'), 'end' => $end->format('d.m.Y H:i'),
                ]], $actor);

                return $existing;
            }

            $booking = RoomBooking::create([
                'room_id' => $room->id,
                'user_id' => $actor->id,
                'booked_by_name' => $bookedFor,
                'start_at' => $start,
                'end_at' => $end,
                'purpose' => $purpose,
            ]);
            Audit::log('room_booking.created', $booking->setRelation('room', $room), array_filter([
                'for' => $bookedFor,
                'start' => $start->format('d.m.Y H:i'), 'end' => $end->format('d.m.Y H:i'),
            ]), $actor);

            return $booking;
        });
    }

    public function reserveDevice(Device $device, Carbon $start, Carbon $end, int $quantity, ?string $purpose, ?string $reservedFor, User $actor): DeviceReservation
    {
        $this->assertNotPast($start);

        // Gleiche Höchstdauer wie bei Ausleihen – sonst ließe sich Bestand auf Jahre blockieren
        // (und die Vormerkung später gar nicht in eine Ausleihe umwandeln)
        $maxDays = (int) $this->settings->get('loans.max_days');
        if ($maxDays > 0 && $start->copy()->startOfDay()->diffInDays($end->copy()->startOfDay()) > $maxDays) {
            throw ValidationException::withMessages(['end_date' => __('Maximale Ausleihdauer: :n Tage.', ['n' => $maxDays])]);
        }

        return DB::transaction(function () use ($device, $start, $end, $quantity, $purpose, $reservedFor, $actor) {
            $device = Device::query()->whereKey($device->id)->lockForUpdate()->firstOrFail();

            $free = $this->availability->free($device, $start, $end);
            if ($quantity > $free) {
                throw ValidationException::withMessages(['quantity' => $free <= 0
                    ? __('Für diesen Zeitraum ist kein Exemplar mehr frei.')
                    : trans_choice('Für diesen Zeitraum ist nur noch :count Exemplar frei.|Für diesen Zeitraum sind nur noch :count Exemplare frei.', $free, ['count' => $free]),
                ]);
            }

            $reservation = DeviceReservation::create([
                'device_id' => $device->id,
                'user_id' => $actor->id,
                'start_at' => $start,
                'end_at' => $end,
                'purpose' => $purpose,
                'quantity' => $quantity,
                'reserved_by_name' => $reservedFor,
                'status' => DeviceReservation::STATUS_PENDING,
            ]);

            Audit::log('device_reservation.created', $reservation->setRelation('device', $device), array_filter([
                'quantity' => $quantity,
                'for' => $reservedFor,
                'start' => $start->format('d.m.Y H:i'),
                'end' => $end->format('d.m.Y H:i'),
            ]), $actor);

            return $reservation;
        });
    }

    private function assertNotPast(Carbon $start, ?Carbon $previousStart = null): void
    {
        if ($this->settings->get('rooms.allow_past')) {
            return;
        }

        // Beim Bearbeiten einer bereits laufenden Buchung den bisherigen Beginn erlauben
        if ($previousStart && $start->equalTo($previousStart)) {
            return;
        }

        // Ganztägig ab heute 00:00 ist kein Nachtragen, sondern der heutige Tag
        if ($start->isToday() && $start->isStartOfDay()) {
            return;
        }

        if ($start->lt(now()->subMinutes(5))) {
            throw ValidationException::withMessages(['start_date' => __('Der Beginn liegt in der Vergangenheit.')]);
        }
    }
}
