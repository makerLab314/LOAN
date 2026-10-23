<?php

namespace App\Policies;

use App\Models\RoomBooking;
use App\Models\User;

class RoomBookingPolicy
{
    public function update(User $user, RoomBooking $booking): bool
    {
        return $booking->user_id === $user->id
            || $user->can('room_bookings.manage_others');
    }

    public function delete(User $user, RoomBooking $booking): bool
    {
        return $this->update($user, $booking);
    }
}
