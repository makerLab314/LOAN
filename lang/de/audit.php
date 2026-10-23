<?php

// Bezeichnungen für Einträge im Protokoll (audit_logs.action)
return [
    'auth' => [
        'login' => 'Anmeldung',
        'logout' => 'Abmeldung',
        'failed' => 'Fehlgeschlagene Anmeldung',
        'password_reset' => 'Passwort zurückgesetzt',
    ],
    'loan' => [
        'created' => 'Ausgeliehen',
        'converted' => 'Vormerkung ausgegeben',
        'returned' => 'Zurückgegeben',
        'partially_returned' => 'Teilweise zurückgegeben',
        'extended' => 'Verlängert',
        'issues_resolved' => 'Fehlendes Zubehör erledigt',
    ],
    'device_reservation' => [
        'created' => 'Vorgemerkt',
        'cancelled' => 'Vormerkung widerrufen',
    ],
    'device' => [
        'created' => 'Gerät angelegt',
        'updated' => 'Gerät geändert',
        'deleted' => 'Gerät gelöscht',
    ],
    'category' => [
        'created' => 'Kategorie angelegt',
        'updated' => 'Kategorie geändert',
        'deleted' => 'Kategorie gelöscht',
    ],
    'room' => [
        'created' => 'Raum angelegt',
        'updated' => 'Raum geändert',
        'deleted' => 'Raum gelöscht',
    ],
    'room_booking' => [
        'created' => 'Raum gebucht',
        'updated' => 'Raumbuchung geändert',
        'deleted' => 'Raumbuchung storniert',
    ],
    'user' => [
        'created' => 'Nutzer angelegt',
        'updated' => 'Nutzer geändert',
        'deleted' => 'Nutzer gelöscht',
        'self_deleted' => 'Konto selbst gelöscht',
        'profile_updated' => 'Profil geändert',
        'email_changed' => 'E-Mail geändert',
        'password_changed' => 'Passwort geändert',
        'admin_created_cli' => 'Admin per Konsole angelegt',
    ],
    'session' => [
        'revoked' => 'Sitzung beendet',
        'revoked_others' => 'Andere Sitzungen beendet',
        'revoked_by_admin' => 'Sitzungen durch Admin beendet',
    ],
    'settings' => [
        'updated' => 'Einstellungen geändert',
    ],
];
