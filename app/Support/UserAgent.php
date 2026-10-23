<?php

namespace App\Support;

/**
 * Sehr einfache User-Agent-Erkennung für die Sitzungsübersicht.
 */
class UserAgent
{
    /**
     * @return array{browser: string, os: string, mobile: bool}
     */
    public static function parse(?string $ua): array
    {
        $ua = (string) $ua;

        $browser = match (true) {
            str_contains($ua, 'Edg/') => 'Edge',
            str_contains($ua, 'OPR/') || str_contains($ua, 'Opera') => 'Opera',
            str_contains($ua, 'SamsungBrowser') => 'Samsung Internet',
            str_contains($ua, 'Firefox/') || str_contains($ua, 'FxiOS') => 'Firefox',
            str_contains($ua, 'CriOS') || str_contains($ua, 'Chrome/') => 'Chrome',
            str_contains($ua, 'Safari/') => 'Safari',
            $ua === '' => __('Unbekannt'),
            default => __('Anderer Browser'),
        };

        $os = match (true) {
            str_contains($ua, 'iPhone') => 'iPhone',
            str_contains($ua, 'iPad') => 'iPad',
            str_contains($ua, 'Android') => 'Android',
            str_contains($ua, 'Windows') => 'Windows',
            str_contains($ua, 'CrOS') => 'ChromeOS',
            str_contains($ua, 'Mac OS X') || str_contains($ua, 'Macintosh') => 'macOS',
            str_contains($ua, 'Linux') => 'Linux',
            default => __('Unbekannt'),
        };

        $mobile = (bool) preg_match('/Mobile|Android|iPhone|iPad/i', $ua);

        return compact('browser', 'os', 'mobile');
    }
}
