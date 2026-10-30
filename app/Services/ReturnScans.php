<?php

namespace App\Services;

use App\Models\Device;
use App\Models\DeviceLoan;
use Illuminate\Validation\ValidationException;

/**
 * Merkt sich in der Sitzung, welche Geräte/Exemplare gerade gescannt (oder per
 * ID eingegeben) wurden. Was mit Scan ausgeliehen wurde (CheckoutScans), darf am
 * Kiosk nur so zurückgegeben werden – das macht "Rückgaben" ohne das Gerät in der
 * Hand mühsam. Ohne Scan Ausgeliehenes geht am Kiosk auch ohne Scan zurück.
 */
class ReturnScans
{
    private const KEY = 'return_scans';

    /** Gültigkeit eines Scans in Sekunden */
    public const TTL = 900;

    public function record(Device $device, ?int $unit): void
    {
        $scans = $this->all();
        $entry = $scans[$device->id] ?? ['at' => 0, 'units' => []];

        $entry['at'] = now()->getTimestamp();
        if ($unit !== null) {
            $entry['units'][$unit] = now()->getTimestamp();
        }

        $scans[$device->id] = $entry;
        session()->put(self::KEY, $scans);
    }

    /**
     * Kiosk-Prüfung vor einer Rückgabe. Gibt die zurückzunehmenden Exemplare zurück
     * (null bei Ausleihen ohne Exemplar-Nummern).
     *
     * Mit Exemplar-Nummern verliehen: jedes zurückgegebene Exemplar muss gescannt sein;
     * ohne Angabe gelten alle offenen Exemplare. Ohne Exemplar-Nummern: das Gerät
     * (oder eines seiner Exemplare) muss gescannt sein.
     *
     * @param  list<int>|null  $units
     * @return list<int>|null
     */
    public function authorize(DeviceLoan $loan, ?array $units): ?array
    {
        // Nur was mit Scan ausgeliehen wurde, braucht auch zur Rückgabe einen Scan –
        // nicht Geräte ohne (abgefallenes) Etikett oder zu groß zum Hochheben
        if (! $loan->checkout_scanned) {
            return $units ? array_values(array_unique(array_map('intval', $units))) : null;
        }

        $loan->loadMissing('units', 'device');
        $entry = $this->all()[$loan->device_id] ?? null;
        $outstanding = $loan->outstandingUnitNumbers();

        if (! $outstanding) {
            if (! $entry) {
                throw ValidationException::withMessages(['code' => __(':title: Bitte das Gerät zuerst scannen oder die Inventar-ID eingeben.', [
                    'title' => $loan->device?->title,
                ])]);
            }

            return null;
        }

        $units = $units ? array_values(array_unique(array_map('intval', $units))) : $outstanding;
        $scanned = array_keys($entry['units'] ?? []);
        $missing = array_diff($units, $scanned);

        if ($missing) {
            throw ValidationException::withMessages(['units' => __('Bitte zuerst scannen: :units', [
                'units' => implode(', ', array_map(fn ($u) => $loan->device->unitLabel($u), $missing)),
            ])]);
        }

        return $units;
    }

    /**
     * Nach der Rückgabe verbrauchen – ein Scan gilt für genau eine Rückgabe.
     *
     * @param  list<int>|null  $units
     */
    public function consume(DeviceLoan $loan, ?array $units): void
    {
        $scans = $this->all();
        // Ohne Scan ausgeliehen: Ein Scan desselben Geräts bleibt für eine andere Ausleihe
        if (! $loan->checkout_scanned || ! isset($scans[$loan->device_id])) {
            return;
        }

        if ($units) {
            foreach ($units as $unit) {
                unset($scans[$loan->device_id]['units'][$unit]);
            }
            if (! $scans[$loan->device_id]['units']) {
                unset($scans[$loan->device_id]);
            }
        } else {
            unset($scans[$loan->device_id]);
        }

        session()->put(self::KEY, $scans);
    }

    /**
     * Stand vor einer Sammel-Rückgabe – scheitert sie, gelten die Scans weiter.
     *
     * @return array<int, array{at: int, units: array<int, int>}>
     */
    public function snapshot(): array
    {
        return $this->all();
    }

    /**
     * @param  array<int, array{at: int, units: array<int, int>}>  $scans
     */
    public function restore(array $scans): void
    {
        session()->put(self::KEY, $scans);
    }

    /**
     * Gültige Scans (abgelaufene werden verworfen).
     *
     * @return array<int, array{at: int, units: array<int, int>}>
     */
    private function all(): array
    {
        $limit = now()->getTimestamp() - self::TTL;
        $scans = [];

        foreach ((array) session()->get(self::KEY, []) as $deviceId => $entry) {
            $units = array_filter((array) ($entry['units'] ?? []), fn ($at) => $at >= $limit);
            if (($entry['at'] ?? 0) >= $limit || $units) {
                $scans[(int) $deviceId] = ['at' => (int) ($entry['at'] ?? 0), 'units' => $units];
            }
        }

        return $scans;
    }
}
