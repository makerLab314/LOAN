<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Support\Audit;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class CreateAdminCommand extends Command
{
    protected $signature = 'loan:create-admin {email} {--name=Admin}';

    protected $description = 'Legt einen Admin an (oder setzt das Passwort eines bestehenden zurück) und gibt ein Zufallspasswort aus';

    public function handle(): int
    {
        $email = strtolower((string) $this->argument('email'));
        if (Validator::make(['email' => $email], ['email' => 'email'])->fails()) {
            $this->error('Ungültige E-Mail-Adresse.');

            return self::FAILURE;
        }

        $password = Str::password(16, symbols: false);

        $user = User::withTrashed()->firstOrNew(['email' => $email]);
        $user->name = $user->name ?: (string) $this->option('name');
        $user->password = Hash::make($password);
        $user->role = User::ROLE_ADMIN;
        $user->locale = $user->locale ?: 'de';
        $user->deleted_at = null;
        $user->save();

        Audit::log('user.admin_created_cli', $user);

        $this->info("Admin {$email} – Passwort: {$password}");
        $this->warn('Bitte nach der ersten Anmeldung unter „Profil“ ändern.');

        return self::SUCCESS;
    }
}
