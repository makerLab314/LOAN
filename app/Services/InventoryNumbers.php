<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Device;
use Illuminate\Support\Str;

/**
 * Inventar-IDs: Kategorie-Kürzel + vierstellige Nummer (KAB-0042),
 * einzelne Exemplare mit Suffix (KAB-0042-3).
 */
class InventoryNumbers
{
    public function next(?Category $category): string
    {
        $prefix = $category?->code ?: 'GER';

        $max = Device::withTrashed()
            ->where('inventory_number', 'like', $prefix.'-%')
            ->pluck('inventory_number')
            ->map(fn ($n) => preg_match('/^'.preg_quote($prefix, '/').'-(\d+)$/', (string) $n, $m) ? (int) $m[1] : 0)
            ->max() ?? 0;

        return sprintf('%s-%04d', $prefix, $max + 1);
    }

    public function suggestCode(string $name, ?int $ignoreId = null): string
    {
        $base = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', Str::ascii($name)) ?: 'GER', 0, 3));
        $base = str_pad($base, 3, 'X');
        $code = $base;
        $i = 2;

        while (Category::withTrashed()->where('code', $code)->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $code = substr($base, 0, 2).$i++;
        }

        return $code;
    }

    /**
     * Löst eine eingegebene/gescannte ID auf.
     * Akzeptiert "KAB-0042", "KAB-0042-3", "kab 42-3" und vollständige /d/…-URLs.
     *
     * @return array{device: Device, unit: int|null}|null
     */
    public function resolve(string $input): ?array
    {
        // Hand-Scanner mit US-Layout an deutscher Tastatur tippen "ß" statt "-"
        $code = mb_strtoupper(str_replace(['ß', 'ẞ', '_'], '-', trim($input)));
        if (preg_match('~/D/([^/?#]+)~', $code, $m)) {
            $code = rawurldecode($m[1]);
        }
        $code = preg_replace('/\s+/', '-', $code) ?? $code;

        if ($code === '') {
            return null;
        }

        if ($device = Device::where('inventory_number', $code)->first()) {
            return ['device' => $device, 'unit' => null];
        }

        if (preg_match('/^(.+)-(\d+)$/', $code, $m)) {
            $device = Device::where('inventory_number', $m[1])->first()
                ?? $this->fuzzy($m[1]);

            if ($device && (int) $m[2] >= 1 && (int) $m[2] <= $device->total_quantity) {
                return ['device' => $device, 'unit' => (int) $m[2]];
            }
        }

        if ($device = $this->fuzzy($code)) {
            return ['device' => $device, 'unit' => null];
        }

        return null;
    }

    /**
     * "KAB-42" → "KAB-0042" (führende Nullen vergessen)
     */
    private function fuzzy(string $code): ?Device
    {
        if (preg_match('/^([A-Z0-9]+)-?0*(\d+)$/', $code, $m)) {
            return Device::where('inventory_number', sprintf('%s-%04d', $m[1], (int) $m[2]))->first();
        }

        return null;
    }
}
