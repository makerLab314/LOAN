<?php

namespace App\Support;

/**
 * Namen von Ausleihenden in Übersichten. Am Kiosk (öffentlich zugängliches Tablet)
 * nur Initialen – wer gerade was hat oder überfällig ist, geht Vorbeikommende nichts an.
 */
class Names
{
    /** "Mia Schulz" → "M. S." (nur am Kiosk) */
    public static function shown(?string $name): string
    {
        $name = trim((string) $name);
        if ($name === '' || ! auth()->user()?->isKiosk()) {
            return $name;
        }

        return collect(preg_split('/\s+/u', $name) ?: [])
            ->take(3)
            ->map(fn (string $part) => mb_substr($part, 0, 1).'.')
            ->implode(' ');
    }
}
