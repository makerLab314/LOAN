<?php

namespace App\Http\Controllers;

use App\Support\Branding;
use App\Support\Settings;
use GdImage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

/**
 * Installierbar als App (PWA): Manifest und Icons aus Name, Akzentfarbe und Logo.
 * Öffentlich, weil Browser beides ohne Cookies abrufen.
 */
class PwaController extends Controller
{
    public const ICON_SIZES = ['180', '192', '512'];

    public function manifest(Branding $branding): JsonResponse
    {
        $icons = [];
        foreach (['192', '512'] as $size) {
            $icons[] = ['src' => route('pwa.icon', $size), 'sizes' => "{$size}x{$size}", 'type' => 'image/png', 'purpose' => 'any'];
            $icons[] = ['src' => route('pwa.icon.maskable', $size), 'sizes' => "{$size}x{$size}", 'type' => 'image/png', 'purpose' => 'maskable'];
        }

        return response()->json([
            'id' => '/',
            'name' => $branding->name(),
            'short_name' => mb_substr($branding->name(), 0, 12),
            'description' => $branding->tagline(),
            'lang' => app()->getLocale(),
            'start_url' => '/',
            'scope' => '/',
            'display' => 'standalone',
            'background_color' => '#f6f6f4',
            'theme_color' => $branding->accent(),
            'icons' => $icons,
            'shortcuts' => [
                ['name' => __('Scannen'), 'url' => '/scan'],
                ['name' => __('Geräte'), 'url' => '/devices'],
                ['name' => __('Ausleihen'), 'url' => '/loans'],
            ],
        ], 200, [
            'Content-Type' => 'application/manifest+json',
            'Cache-Control' => 'public, max-age=3600',
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    public function icon(Branding $branding, Settings $settings, string $size, bool $maskable = false): Response
    {
        abort_unless(in_array($size, self::ICON_SIZES, true), 404);

        $logo = (string) $settings->get('general.logo_path');
        $key = 'pwa.icon.'.md5(implode('|', [$size, $maskable, $branding->accent(), $branding->accentForeground(), $logo]));

        // Einmal rendern, dann aus dem Cache (base64, damit es auch im Datenbank-Cache sicher ist)
        $png = base64_decode(Cache::rememberForever($key, fn () => base64_encode($this->render((int) $size, $maskable, $branding, $logo))));

        return response($png, 200, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }

    public function maskableIcon(Branding $branding, Settings $settings, string $size): Response
    {
        return $this->icon($branding, $settings, $size, maskable: true);
    }

    /**
     * Akzentfläche mit Box-Symbol – oder das hochgeladene Logo auf Weiß. 4-fach
     * gezeichnet und verkleinert, weil GD dicke Linien nicht glättet.
     */
    private function render(int $size, bool $maskable, Branding $branding, string $logo): string
    {
        $scale = 4;
        $s = $size * $scale;
        $img = imagecreatetruecolor($s, $s);
        imagealphablending($img, false);
        imagefill($img, 0, 0, imagecolorallocatealpha($img, 0, 0, 0, 127));
        imagealphablending($img, true);

        $logoImage = $this->loadLogo($logo);
        $bg = $logoImage ? [255, 255, 255] : $this->rgb($branding->accent());
        $bgColor = imagecolorallocate($img, ...$bg);

        // Maskable: vollflächig (das System schneidet zu), sonst abgerundetes Quadrat
        if ($maskable) {
            imagefilledrectangle($img, 0, 0, $s, $s, $bgColor);
        } else {
            $this->roundedRect($img, $s, (int) ($s * 0.22), $bgColor);
        }

        // Inhalt im sicheren Bereich (Maskable: mittlere 80 %, wir nutzen ~56 %)
        $content = (int) ($s * ($maskable ? 0.5 : 0.6));
        $offset = (int) (($s - $content) / 2);

        if ($logoImage) {
            $w = imagesx($logoImage);
            $h = imagesy($logoImage);
            $ratio = min($content / $w, $content / $h);
            $dw = (int) ($w * $ratio);
            $dh = (int) ($h * $ratio);
            imagecopyresampled($img, $logoImage, (int) (($s - $dw) / 2), (int) (($s - $dh) / 2), 0, 0, $dw, $dh, $w, $h);
        } else {
            $this->drawBox($img, $offset, $content, imagecolorallocate($img, ...$this->rgb($branding->accentForeground())));
        }

        $out = imagecreatetruecolor($size, $size);
        imagealphablending($out, false);
        imagesavealpha($out, true);
        imagecopyresampled($out, $img, 0, 0, 0, 0, $size, $size, $s, $s);

        ob_start();
        imagepng($out, null, 9);

        return (string) ob_get_clean();
    }

    private function loadLogo(string $path): ?GdImage
    {
        if ($path === '' || ! Storage::disk('public')->exists($path)) {
            return null;
        }
        $image = @imagecreatefromstring((string) Storage::disk('public')->get($path));

        return $image instanceof GdImage ? $image : null;
    }

    /** Lucide "box" auf 24er-Raster, mit runden Linienenden */
    private function drawBox(GdImage $img, int $offset, int $content, int $color): void
    {
        $unit = $content / 24;
        $p = fn (float $x, float $y) => [(int) round($offset + $x * $unit), (int) round($offset + $y * $unit)];
        $lines = [
            [[21, 8], [12, 3], [3, 8], [3, 16], [12, 21], [21, 16], [21, 8]],
            [[3, 8], [12, 13], [21, 8]],
            [[12, 13], [12, 21]],
        ];
        $thickness = max(1, (int) round(2 * $unit));
        imagesetthickness($img, $thickness);
        foreach ($lines as $line) {
            for ($i = 1; $i < count($line); $i++) {
                [$x1, $y1] = $p(...$line[$i - 1]);
                [$x2, $y2] = $p(...$line[$i]);
                imageline($img, $x1, $y1, $x2, $y2, $color);
                imagefilledellipse($img, $x2, $y2, $thickness, $thickness, $color);
            }
            [$x0, $y0] = $p(...$line[0]);
            imagefilledellipse($img, $x0, $y0, $thickness, $thickness, $color);
        }
    }

    private function roundedRect(GdImage $img, int $s, int $r, int $color): void
    {
        imagefilledrectangle($img, $r, 0, $s - $r, $s, $color);
        imagefilledrectangle($img, 0, $r, $s, $s - $r, $color);
        foreach ([[$r, $r], [$s - $r, $r], [$r, $s - $r], [$s - $r, $s - $r]] as [$x, $y]) {
            imagefilledellipse($img, $x, $y, $r * 2, $r * 2, $color);
        }
    }

    /** @return array{int, int, int} */
    private function rgb(string $hex): array
    {
        [$r, $g, $b] = sscanf($hex, '#%02x%02x%02x');

        return [(int) $r, (int) $g, (int) $b];
    }
}
