<?php

namespace Database\Factories;

use App\Models\Room;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Room>
 */
class RoomFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => 'Raum '.fake()->unique()->numberBetween(1, 999),
            'location' => fake()->streetName(),
            'description' => fake()->sentence(),
        ];
    }
}
