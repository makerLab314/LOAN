<?php

namespace App\Services;

use App\Models\Device;
use App\Models\DeviceLoan;
use App\Models\DeviceReservation;
use Illuminate\Support\Carbon;

/**
 * Berechnet, wie viele Exemplare eines Geräts in einem Zeitraum frei sind.
 *
 * Frei = Gesamtmenge
 *      − Menge aktiver Ausleihen, die den Zeitraum betreffen
 *      − höchste gleichzeitige Menge aktiver Vormerkungen im Zeitraum
 */
class Availability
{
    /**
     * @param  int|null  $exceptLoanId  Ausleihe ignorieren (z. B. beim Verlängern)
     * @param  int|null  $exceptReservationId  Vormerkung ignorieren (z. B. beim Umwandeln)
     */
    public function free(
        Device $device,
        Carbon $from,
        Carbon $to,
        ?int $exceptLoanId = null,
        ?int $exceptReservationId = null,
    ): int {
        return max(0, $device->total_quantity
            - $this->loanedDuring($device, $from, $exceptLoanId)
            - $this->peakReserved($device, $from, $to, $exceptReservationId));
    }

    /**
     * Aktive Ausleihen zählen, wenn sie bis in den Zeitraum hineinreichen
     * oder bereits überfällig sind (dann ist unklar, wann sie zurückkommen).
     */
    public function loanedDuring(Device $device, Carbon $from, ?int $exceptLoanId = null): int
    {
        return (int) DeviceLoan::query()
            ->active()
            ->where('device_id', $device->id)
            ->when($exceptLoanId, fn ($q) => $q->where('id', '!=', $exceptLoanId))
            ->where(function ($q) use ($from) {
                $q->whereDate('loan_end_date', '>=', $from->toDateString())
                    ->orWhereDate('loan_end_date', '<', today()->toDateString());
            })
            ->get(['quantity', 'returned_quantity'])
            ->sum(fn (DeviceLoan $l) => $l->outstanding_quantity);
    }

    /**
     * Maximale gleichzeitig vorgemerkte Menge im Zeitraum (Sweep-Line),
     * damit sich nicht überschneidende Vormerkungen nicht addiert werden.
     */
    public function peakReserved(Device $device, Carbon $from, Carbon $to, ?int $exceptReservationId = null): int
    {
        $reservations = DeviceReservation::query()
            ->active()
            ->where('device_id', $device->id)
            ->overlapping($from, $to)
            ->when($exceptReservationId, fn ($q) => $q->where('id', '!=', $exceptReservationId))
            ->get(['start_at', 'end_at', 'quantity']);

        $events = [];
        foreach ($reservations as $r) {
            $events[] = [max($r->start_at->getTimestamp(), $from->getTimestamp()), $r->quantity];
            $events[] = [min($r->end_at->getTimestamp(), $to->getTimestamp()), -$r->quantity];
        }

        // Bei gleichem Zeitpunkt zuerst Enden verarbeiten (halboffene Intervalle)
        usort($events, fn ($a, $b) => $a[0] <=> $b[0] ?: $a[1] <=> $b[1]);

        $current = $peak = 0;
        foreach ($events as [, $delta]) {
            $current += $delta;
            $peak = max($peak, $current);
        }

        return $peak;
    }
}
