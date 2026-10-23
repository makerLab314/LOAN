<?php

namespace Database\Factories;

use App\Models\Device;
use App\Models\DeviceReservation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DeviceReservation>
 */
class DeviceReservationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'device_id' => Device::factory(),
            'user_id' => User::factory(),
            'start_at' => now()->addDay()->setTime(9, 0),
            'end_at' => now()->addDays(2)->setTime(17, 0),
            'quantity' => 1,
            'status' => DeviceReservation::STATUS_PENDING,
        ];
    }
}
