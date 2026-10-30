<?php

namespace App\Support;

/**
 * Schlüssel für Rate-Limits: IPv6-Anschlüsse zählen als ganzes /64-Netz –
 * pro Adresse ließe sich jede Sperre mit beliebig vielen Adressen umgehen.
 */
class ClientIp
{
    public static function bucket(?string $ip): string
    {
        $bin = $ip ? @inet_pton($ip) : false;

        // IPv6 (außer IPv4-mapped ::ffff:a.b.c.d): ein Anschluss = ein /64-Netz
        if ($bin !== false && strlen($bin) === 16 && substr($bin, 0, 12) !== str_repeat("\0", 10)."\xff\xff") {
            return inet_ntop(substr($bin, 0, 8).str_repeat("\0", 8)).'/64';
        }

        return (string) $ip;
    }
}
