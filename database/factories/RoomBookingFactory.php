<?php

namespace Database\Factories;

use App\Models\Room;
use App\Models\RoomBooking;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RoomBooking>
 */
class RoomBookingFactory extends Factory
{
    public function definition(): array
    {
        return [
            'room_id' => Room::factory(),
            'user_id' => User::factory(),
            'start_at' => now()->addDay()->setTime(10, 0),
            'end_at' => now()->addDay()->setTime(12, 0),
            'purpose' => fake()->sentence(3),
        ];
    }
}
