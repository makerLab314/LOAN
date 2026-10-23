<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Device;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Device>
 */
class DeviceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'inventory_number' => 'GER-'.str_pad((string) fake()->unique()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'title' => fake()->words(2, true),
            'description' => fake()->sentence(),
            'category_id' => Category::factory(),
            'total_quantity' => 1,
        ];
    }

    public function quantity(int $n): static
    {
        return $this->state(['total_quantity' => $n]);
    }
}
