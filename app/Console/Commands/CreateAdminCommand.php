<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Support\Audit;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
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
        // Bestehendes Konto: "Angemeldet bleiben" überall ungültig machen
        $user->remember_token = Str::random(60);
        $user->role = User::ROLE_ADMIN;
        $user->must_change_password = true;
        $user->locale = $user->locale ?: 'de';
        $user->deleted_at = null;
        $user->save();

        // Wer das Konto bisher (womöglich unbefugt) nutzte, ist danach abgemeldet
        DB::table('sessions')->where('user_id', $user->id)->delete();

        Audit::log('user.admin_created_cli', $user);

        $this->info("Admin {$email} – Passwort: {$password}");
        $this->warn('Bei der ersten Anmeldung wird ein eigenes Passwort verlangt.');

        return self::SUCCESS;
    }
}
