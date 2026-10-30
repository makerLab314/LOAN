<?php

namespace App\Services;

use App\Models\Device;

/**
 * Merkt sich in der Sitzung, welche Geräte vor dem Ausleihen gescannt (oder per ID
 * eingegeben) wurden. So ausgeliehene Geräte gehen am Kiosk nur per Scan zurück
 * (siehe ReturnScans) – Geräte ohne Etikett oder zu groß zum Scannen nicht.
 */
class CheckoutScans
{
    private const KEY = 'checkout_scans';

    /** Gültigkeit eines Scans in Sekunden */
    public const TTL = 900;

    public function record(Device $device): void
    {
        $scans = $this->all();
        $scans[$device->id] = now()->getTimestamp();
        session()->put(self::KEY, $scans);
    }

    public function scanned(Device $device): bool
    {
        return isset($this->all()[$device->id]);
    }

    /**
     * @return array<int, int> Geräte-ID → Zeitpunkt des Scans
     */
    private function all(): array
    {
        $limit = now()->getTimestamp() - self::TTL;

        return array_filter(array_map('intval', (array) session()->get(self::KEY, [])), fn (int $at) => $at >= $limit);
    }
}
