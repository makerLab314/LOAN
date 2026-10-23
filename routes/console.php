<?php

use Illuminate\Support\Facades\Schedule;

// Tägliches Backup von Datenbank und Uploads (abschaltbar unter Einstellungen → Backups)
Schedule::command('loan:backup')->dailyAt('02:30')->withoutOverlapping()->onOneServer();

// Abgelaufene Sitzungen & Passwort-Reset-Tokens aufräumen
Schedule::command('auth:clear-resets')->daily();
