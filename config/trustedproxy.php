<?php

/*
|--------------------------------------------------------------------------
| Vertrauenswürdige Reverse Proxys
|--------------------------------------------------------------------------
| Liest Laravels TrustProxies-Middleware. Steht hier statt in bootstrap/app.php,
| weil env() dort vor dem Laden der .env-Datei läuft (und mit gecachter Konfiguration
| nie). Nur die genaue Adresse des Proxys eintragen, nie ganze private Netze: Dort
| sitzen auch die Clients (Schulnetz, WLAN) und könnten X-Forwarded-For fälschen.
*/

return [
    'proxies' => env('TRUSTED_PROXIES') ?: null,
];
