<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Device;
use App\Models\DeviceLoan;
use App\Models\Room;
use App\Models\RoomBooking;
use App\Services\InventoryNumbers;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Globale Suche (Strg+K). Liefert JSON – die Ausgabe erfolgt im Browser
 * ausschließlich per textContent, nie als HTML.
 */
class SearchController extends Controller
{
    public function __invoke(Request $request, InventoryNumbers $numbers): JsonResponse
    {
        $q = trim(Str::limit((string) $request->query('q'), 100, ''));
        if (mb_strlen($q) < 1) {
            return response()->json(['results' => []]);
        }

        $results = [];
        $like = '%'.$q.'%';
        // Raumplanung: keine Geräte, Ausleihen und Kategorien
        $devices = $request->user()->usesDevices();

        // Exakte ID (inkl. Exemplar) zuerst
        if ($devices && $hit = $numbers->resolve($q)) {
            $device = $hit['device'];
            $results[] = [
                'type' => 'id',
                'group' => __('Inventar-ID'),
                'title' => $hit['unit'] ? $device->unitLabel($hit['unit']) : (string) $device->inventory_number,
                'subtitle' => $device->title,
                'url' => route('devices.show', array_filter(['device' => $device->id, 'unit' => $hit['unit']])),
            ];
        }

        if ($devices) {
            Device::query()
                ->with('category')
                ->where(fn ($w) => $w->where('title', 'like', $like)
                    ->orWhere('inventory_number', 'like', $like)
                    ->orWhere('description', 'like', $like))
                ->orderBy('title')->limit(8)->get()
                ->each(function (Device $d) use (&$results) {
                    $results[] = [
                        'type' => 'device',
                        'group' => __('Geräte'),
                        'title' => $d->title,
                        'subtitle' => trim($d->inventory_number.' · '.($d->category?->name ?? ''), ' ·'),
                        'url' => route('devices.show', $d),
                    ];
                });

            DeviceLoan::query()->active()->with('device')
                ->where('borrower_name', 'like', $like)
                ->orderBy('loan_end_date')->limit(6)->get()
                ->each(function (DeviceLoan $l) use (&$results) {
                    $results[] = [
                        'type' => 'loan',
                        'group' => __('Ausleihen'),
                        'title' => $l->borrower_name,
                        'subtitle' => ($l->device?->title ?? '—').' · '.__('bis :date', ['date' => $l->loan_end_date->format('d.m.Y')]),
                        'url' => route('devices.show', $l->device_id),
                    ];
                });
        }

        Room::query()
            ->where(fn ($w) => $w->where('name', 'like', $like)->orWhere('location', 'like', $like))
            ->orderBy('name')->limit(5)->get()
            ->each(function (Room $r) use (&$results, $request) {
                $results[] = [
                    'type' => 'room',
                    'group' => __('Räume'),
                    'title' => $r->name,
                    'subtitle' => $r->location,
                    'url' => $request->user()->can('rooms.book') ? route('bookings.create', $r) : route('rooms.index'),
                ];
            });

        // Anstehende Raumbuchungen nach Anlass oder Ansprechperson
        RoomBooking::query()->upcoming()->with(['room', 'user'])
            ->where(fn ($w) => $w->where('purpose', 'like', $like)->orWhere('booked_by_name', 'like', $like))
            ->orderBy('start_at')->limit(6)->get()
            ->each(function (RoomBooking $b) use (&$results, $request) {
                $results[] = [
                    'type' => 'booking',
                    'group' => __('Raumbuchungen'),
                    'title' => (string) $b->purpose,
                    'subtitle' => ($b->room?->name ?? '—').' · '.$b->start_at->translatedFormat('D d.m.').', '.$b->timeRange().' · '.$b->displayName(),
                    'url' => $request->user()->can('update', $b) ? route('bookings.edit', $b) : route('bookings.index', ['room' => $b->room_id]),
                ];
            });

        if ($devices) {
            Category::query()->where('name', 'like', $like)->orderBy('name')->limit(4)->get()
                ->each(function (Category $c) use (&$results) {
                    $results[] = [
                        'type' => 'category',
                        'group' => __('Kategorien'),
                        'title' => $c->name,
                        'subtitle' => $c->code,
                        'url' => route('devices.index', ['category' => $c->id]),
                    ];
                });
        }

        return response()->json(['results' => $results]);
    }
}
