<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Admin-Einstellungen (Tabelle "settings", Werte als JSON), mit Cache.
 * Standardwerte stehen in config/loan.php → "defaults".
 */
class Settings
{
    private const CACHE_KEY = 'loan.settings';

    /** @var array<string, mixed>|null */
    private ?array $values = null;

    public function get(string $key, mixed $default = null): mixed
    {
        $values = $this->all();

        if (array_key_exists($key, $values)) {
            return $values[$key];
        }

        return $default ?? config('loan.defaults.'.$key);
    }

    /**
     * Gespeicherte Werte, ergänzt um die Standardwerte.
     *
     * @return array<string, mixed>
     */
    public function all(): array
    {
        if ($this->values !== null) {
            return $this->values;
        }

        $stored = [];
        try {
            $stored = Cache::rememberForever(self::CACHE_KEY, function () {
                if (! Schema::hasTable('settings')) {
                    return [];
                }

                return DB::table('settings')->pluck('value', 'key')
                    ->map(fn ($v) => json_decode((string) $v, true))
                    ->all();
            });
        } catch (Throwable) {
            // Datenbank (noch) nicht verfügbar, z. B. vor der ersten Migration
        }

        return $this->values = array_merge(config('loan.defaults', []), $stored);
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public function set(array $values): void
    {
        $now = now();
        foreach ($values as $key => $value) {
            DB::table('settings')->updateOrInsert(
                ['key' => $key],
                ['value' => json_encode($value), 'updated_at' => $now, 'created_at' => $now],
            );
        }

        $this->flush();
    }

    public function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
        $this->values = null;
    }

    /**
     * Rechte einer Rolle.
     *
     * @return list<string>
     */
    public function permissionsFor(string $role): array
    {
        if ($role === 'administration') {
            return array_keys(config('loan.permissions'));
        }

        $granted = (array) $this->get('permissions.'.$role, []);

        return array_values(array_filter($granted, fn ($p) => self::assignable($role, $p)));
    }

    /**
     * Darf das Recht dieser Rolle überhaupt gegeben werden? Admin-Rechte nie,
     * der Kiosk-Rolle nur ausdrücklich freigegebene.
     */
    public static function assignable(string $role, mixed $permission): bool
    {
        $entry = is_string($permission) ? (config('loan.permissions')[$permission] ?? null) : null;

        return $entry !== null
            && empty($entry['admin_only'])
            && ($role !== 'kiosk' || ! empty($entry['kiosk']));
    }
}
