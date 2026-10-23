<?php

namespace Tests;

use App\Models\User;
use App\Support\Settings;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Tests laufen ohne gebaute Assets
        $this->withoutVite();
    }

    protected function admin(array $attributes = []): User
    {
        return User::factory()->admin()->create($attributes);
    }

    protected function moderator(array $attributes = []): User
    {
        return User::factory()->create($attributes);
    }

    /**
     * @param  array<string, mixed>  $values
     */
    protected function setting(array $values): void
    {
        app(Settings::class)->set($values);
    }
}
