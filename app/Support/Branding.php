<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

/**
 * Darstellung aus den Admin-Einstellungen (Name, Farben, Logo).
 */
class Branding
{
    public function __construct(private Settings $settings) {}

    public function name(): string
    {
        return (string) $this->settings->get('general.app_name') ?: 'LOAN';
    }

    public function tagline(): string
    {
        return (string) $this->settings->get('general.tagline');
    }

    public function accent(): string
    {
        $color = (string) $this->settings->get('general.accent_color');

        return preg_match('/^#[0-9a-fA-F]{6}$/', $color) ? $color : '#d97706';
    }

    /**
     * Schwarz oder Weiß – je nachdem, was auf der Akzentfarbe besser lesbar ist.
     */
    public function accentForeground(): string
    {
        [$r, $g, $b] = sscanf($this->accent(), '#%02x%02x%02x');
        $lin = fn (int $c) => ($c /= 255) <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
        $luminance = 0.2126 * $lin($r) + 0.7152 * $lin($g) + 0.0722 * $lin($b);

        return $luminance > 0.36 ? '#111111' : '#ffffff';
    }

    public function logoUrl(): ?string
    {
        $path = $this->settings->get('general.logo_path');

        return $path ? Storage::disk('public')->url($path) : null;
    }

    public function faviconUrl(): string
    {
        $path = $this->settings->get('general.favicon_path');

        return $path ? Storage::disk('public')->url($path) : asset('favicon.ico');
    }
}
