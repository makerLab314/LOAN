<?php

namespace Database\Factories;

use App\Models\Device;
use App\Models\DeviceLoan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DeviceLoan>
 */
class DeviceLoanFactory extends Factory
{
    public function definition(): array
    {
        return [
            'device_id' => Device::factory(),
            'borrower_name' => fake()->name(),
            'quantity' => 1,
            'returned_quantity' => 0,
            'loan_start_date' => today(),
            'loan_end_date' => today()->addDays(7),
        ];
    }
}
