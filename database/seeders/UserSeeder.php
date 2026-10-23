<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    /**
     * Legt einen ersten Admin an – mit Zufallspasswort statt eines festen
     * Standardpassworts. Alternativ: php artisan loan:create-admin <email>
     */
    public function run(): void
    {
        $email = getenv('ADMIN_EMAIL') ?: 'admin@example.org';

        if (User::where('email', $email)->exists()) {
            return;
        }

        $password = getenv('ADMIN_PASSWORD') ?: Str::password(16, symbols: false);

        $user = new User([
            'name' => 'Admin',
            'email' => $email,
            'password' => Hash::make($password),
        ]);
        $user->role = User::ROLE_ADMIN;
        $user->save();

        $this->command->info("Admin angelegt: {$email} / {$password}");
    }
}
