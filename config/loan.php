<?php

/*
|--------------------------------------------------------------------------
| LOAN – Rechte & Standard-Einstellungen
|--------------------------------------------------------------------------
| Alle Einstellungen können Admins unter /admin/settings ändern. Hier stehen
| nur die Standardwerte (werden verwendet, solange nichts gespeichert ist).
*/

return [

    // Zielordner für Backups (im Docker-Setup ein Volume nach ./data/backups)
    'backup_path' => env('BACKUP_PATH', storage_path('app/backups')),

    /*
     * Rechtekatalog. "admin_only" = nicht an andere Rollen vergebbar
     * (verhindert, dass man sich aussperrt). "kiosk" = darf der Kiosk-Rolle
     * gegeben werden (gemeinsames Tablet ohne persönliche Anmeldung).
     * "devices" = gehört zur Geräteverwaltung – nie für die Raumplanung.
     */
    'permissions' => [
        'loans.create' => ['group' => 'Ausleihe', 'label' => 'Geräte ausleihen & Vormerkungen umwandeln', 'kiosk' => true, 'devices' => true],
        'loans.return' => ['group' => 'Ausleihe', 'label' => 'Rücknahme, Teilrückgabe & Verlängerung', 'kiosk' => true, 'devices' => true],
        'reservations.create' => ['group' => 'Ausleihe', 'label' => 'Geräte vormerken', 'kiosk' => true, 'devices' => true],
        'reservations.manage_others' => ['group' => 'Ausleihe', 'label' => 'Vormerkungen anderer widerrufen', 'devices' => true],
        'rooms.book' => ['group' => 'Räume', 'label' => 'Räume buchen', 'kiosk' => true],
        'room_bookings.manage_others' => ['group' => 'Räume', 'label' => 'Raumbuchungen anderer bearbeiten & löschen'],
        'devices.manage' => ['group' => 'Stammdaten', 'label' => 'Geräte anlegen, bearbeiten & löschen', 'devices' => true],
        'categories.manage' => ['group' => 'Stammdaten', 'label' => 'Kategorien verwalten', 'devices' => true],
        'rooms.manage' => ['group' => 'Stammdaten', 'label' => 'Räume verwalten'],
        'labels.print' => ['group' => 'Stammdaten', 'label' => 'Etiketten drucken', 'devices' => true],
        'audit.view' => ['group' => 'Verwaltung', 'label' => 'Protokoll einsehen'],
        'users.manage' => ['group' => 'Verwaltung', 'label' => 'Nutzer verwalten', 'admin_only' => true],
        'settings.manage' => ['group' => 'Verwaltung', 'label' => 'Einstellungen ändern', 'admin_only' => true],
    ],

    'roles' => [
        'administration' => 'Administration',
        'moderation' => 'Moderation',
        // Nur Räume, Buchungen und Kalender – Geräte und Ausleihe sind ausgeblendet
        'raumplanung' => 'Raumplanung',
        'kiosk' => 'Kiosk',
    ],

    'defaults' => [
        // Allgemein
        'general.app_name' => env('APP_NAME', 'LOAN'),
        'general.tagline' => 'Geräteausleihe & Raumbuchung',
        'general.accent_color' => '#d97706',
        'general.logo_path' => null,
        'general.favicon_path' => null,
        'general.default_locale' => env('APP_LOCALE', 'de'),
        'general.timezone' => env('APP_TIMEZONE', 'Europe/Berlin'),

        // Ausleihe
        'loans.default_days' => 7,
        'loans.max_days' => 0,                 // 0 = unbegrenzt
        'loans.convert_lead_hours' => 24,      // Vormerkung so viele Stunden vor Start umwandelbar
        'loans.accessories_check_required' => true,
        'loans.units_required' => false,

        // Räume
        'rooms.allow_past' => false,
        'rooms.max_hours' => 0,                // 0 = unbegrenzt

        // Sicherheit
        'security.password_min' => 10,
        'security.password_uncompromised' => false,
        'security.allow_self_delete' => false,
        'security.session_lifetime' => (int) env('SESSION_LIFETIME', 120),

        // Etiketten
        'labels.format' => 'a4_3x8',
        'labels.code_type' => 'qr',            // qr | barcode | both | text
        'labels.show_title' => true,
        'labels.show_app_name' => true,
        'labels.note' => '',                   // Zusatzzeile, z. B. "Eigentum des MakerLab"

        // Rechtliches (Markdown)
        'legal.imprint' => '',
        'legal.privacy' => '',

        // Backups
        'backups.enabled' => true,
        'backups.keep_days' => 14,
        'backups.last_run' => null,
        'backups.last_status' => null,

        // Rechte je Rolle (Administration hat immer alle Rechte)
        'permissions.moderation' => [
            'loans.create',
            'loans.return',
            'reservations.create',
            'rooms.book',
            'labels.print',
        ],
        // Raumplanung: Rechte mit "devices" => true sind nicht vergebbar
        'permissions.raumplanung' => [
            'rooms.book',
            'room_bookings.manage_others',
        ],
        // Kiosk: nur Rechte mit "kiosk" => true sind überhaupt vergebbar
        'permissions.kiosk' => [
            'loans.create',
            'loans.return',
            'reservations.create',
            'rooms.book',
        ],
    ],

    /*
     * Etikettenformate (Maße in mm)
     */
    // Maße in mm. gap_x/gap_y: Abstand zwischen den Etiketten (fehlt = 0).
    // group: Überschrift in der Auswahl. Rollen (1 × 1) drucken ein Etikett pro Seite.
    'label_formats' => [
        'a4_3x8' => ['label' => 'A4 · 3 × 8 (70 × 37 mm)', 'group' => 'A4-Bögen', 'page' => 'A4', 'cols' => 3, 'rows' => 8, 'w' => 70, 'h' => 37, 'margin_top' => 0, 'margin_left' => 0],
        'a4_2x7' => ['label' => 'A4 · 2 × 7 (105 × 42 mm)', 'group' => 'A4-Bögen', 'page' => 'A4', 'cols' => 2, 'rows' => 7, 'w' => 105, 'h' => 42.4, 'margin_top' => 0, 'margin_left' => 0],
        'a4_3x7' => ['label' => 'A4 · 3 × 7 (63,5 × 38,1 mm, z. B. Avery L7160)', 'group' => 'A4-Bögen', 'page' => 'A4', 'cols' => 3, 'rows' => 7, 'w' => 63.5, 'h' => 38.1, 'margin_top' => 15.15, 'margin_left' => 7.25, 'gap_x' => 2.5],
        'a4_2x8' => ['label' => 'A4 · 2 × 8 (99,1 × 33,9 mm, z. B. Avery L7162)', 'group' => 'A4-Bögen', 'page' => 'A4', 'cols' => 2, 'rows' => 8, 'w' => 99.1, 'h' => 33.9, 'margin_top' => 12.9, 'margin_left' => 4.65, 'gap_x' => 2.5],
        'a4_4x10' => ['label' => 'A4 · 4 × 10 (48,5 × 25,4 mm)', 'group' => 'A4-Bögen', 'page' => 'A4', 'cols' => 4, 'rows' => 10, 'w' => 48.5, 'h' => 25.4, 'margin_top' => 21.5, 'margin_left' => 8],
        'a4_5x13' => ['label' => 'A4 · 5 × 13 (38,1 × 21,2 mm, z. B. Avery L7651)', 'group' => 'A4-Bögen', 'page' => 'A4', 'cols' => 5, 'rows' => 13, 'w' => 38.1, 'h' => 21.2, 'margin_top' => 10.7, 'margin_left' => 4.75, 'gap_x' => 2.5],
        'a4_4x16' => ['label' => 'A4 · 4 × 16 (48,5 × 16,9 mm, z. B. Zweckform 3657) – Kabel', 'group' => 'A4-Bögen', 'page' => 'A4', 'cols' => 4, 'rows' => 16, 'w' => 48.5, 'h' => 16.9, 'margin_top' => 13.3, 'margin_left' => 8],
        'roll_62x29' => ['label' => 'Brother 62 × 29 mm (DK-11209)', 'group' => 'Etikettendrucker', 'page' => '62mm 29mm', 'cols' => 1, 'rows' => 1, 'w' => 62, 'h' => 29, 'margin_top' => 0, 'margin_left' => 0],
        'roll_62x40' => ['label' => 'Brother Endlos 62 mm, 40 mm lang (DK-22205 / Folie DK-22212)', 'group' => 'Etikettendrucker', 'page' => '62mm 40mm', 'cols' => 1, 'rows' => 1, 'w' => 62, 'h' => 40, 'margin_top' => 0, 'margin_left' => 0],
        'roll_90x29' => ['label' => 'Brother 90 × 29 mm (DK-11201)', 'group' => 'Etikettendrucker', 'page' => '90mm 29mm', 'cols' => 1, 'rows' => 1, 'w' => 90, 'h' => 29, 'margin_top' => 0, 'margin_left' => 0],
        'roll_90x38' => ['label' => 'Brother 90 × 38 mm (DK-11208)', 'group' => 'Etikettendrucker', 'page' => '90mm 38mm', 'cols' => 1, 'rows' => 1, 'w' => 90, 'h' => 38, 'margin_top' => 0, 'margin_left' => 0],
        'roll_54x17' => ['label' => 'Brother 54 × 17 mm (DK-11204) – Kabel', 'group' => 'Etikettendrucker', 'page' => '54mm 17mm', 'cols' => 1, 'rows' => 1, 'w' => 54, 'h' => 17, 'margin_top' => 0, 'margin_left' => 0],
        'roll_54x25' => ['label' => 'Dymo 54 × 25 mm (11352)', 'group' => 'Etikettendrucker', 'page' => '54mm 25mm', 'cols' => 1, 'rows' => 1, 'w' => 54, 'h' => 25, 'margin_top' => 0, 'margin_left' => 0],
        'roll_57x32' => ['label' => 'Dymo 57 × 32 mm (11354)', 'group' => 'Etikettendrucker', 'page' => '57mm 32mm', 'cols' => 1, 'rows' => 1, 'w' => 57, 'h' => 32, 'margin_top' => 0, 'margin_left' => 0],
        'roll_51x19' => ['label' => 'Dymo 51 × 19 mm (11355) – Kabel', 'group' => 'Etikettendrucker', 'page' => '51mm 19mm', 'cols' => 1, 'rows' => 1, 'w' => 51, 'h' => 19, 'margin_top' => 0, 'margin_left' => 0],
        'tape_12' => ['label' => 'Schriftband 12 mm, 45 mm lang (z. B. P-touch)', 'group' => 'Etikettendrucker', 'page' => '45mm 12mm', 'cols' => 1, 'rows' => 1, 'w' => 45, 'h' => 12, 'margin_top' => 0, 'margin_left' => 0],
        'tape_24' => ['label' => 'Schriftband 24 mm, 60 mm lang (z. B. P-touch)', 'group' => 'Etikettendrucker', 'page' => '60mm 24mm', 'cols' => 1, 'rows' => 1, 'w' => 60, 'h' => 24, 'margin_top' => 0, 'margin_left' => 0],
    ],
];
