<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'locale' => 'de',
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (User $user) {
            $user->role ??= User::ROLE_MODERATOR;
        });
    }

    public function admin(): static
    {
        return $this->afterMaking(fn (User $user) => $user->role = User::ROLE_ADMIN);
    }

    public function kiosk(): static
    {
        return $this->afterMaking(fn (User $user) => $user->role = User::ROLE_KIOSK);
    }

    public function roomPlanner(): static
    {
        return $this->afterMaking(fn (User $user) => $user->role = User::ROLE_ROOMS);
    }
}
