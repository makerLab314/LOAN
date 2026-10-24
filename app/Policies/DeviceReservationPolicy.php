<?php

namespace App\Policies;

use App\Models\DeviceReservation;
use App\Models\User;
use App\Support\Settings;

class DeviceReservationPolicy
{
    public function cancel(User $user, DeviceReservation $reservation): bool
    {
        // Am Kiosk gehören alle Vormerkungen demselben Konto – sonst könnte jeder fremde widerrufen
        if (! $reservation->isActive() || $user->isKiosk()) {
            return false;
        }

        return $reservation->user_id === $user->id
            || $user->can('reservations.manage_others');
    }

    public function convert(User $user, DeviceReservation $reservation): bool
    {
        if (! $reservation->isActive() || ! $user->can('loans.create')) {
            return false;
        }

        // Erst kurz vor Beginn ausgeben (Vorlauf aus den Einstellungen)
        $lead = (int) app(Settings::class)->get('loans.convert_lead_hours');
        if ($reservation->start_at->copy()->subHours($lead)->isFuture()) {
            return false;
        }

        return $reservation->user_id === $user->id
            || $user->can('reservations.manage_others');
    }
}
