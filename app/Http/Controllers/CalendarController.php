<?php

namespace App\Http\Controllers;

use App\Models\DeviceLoan;
use App\Models\DeviceReservation;
use App\Models\Room;
use App\Models\RoomBooking;
use App\Support\Names;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class CalendarController extends Controller
{
    public function index(): View
    {
        return view('calendar', ['rooms' => Room::orderBy('name')->get()]);
    }

    /**
     * Event-Feed für FullCalendar.
     */
    public function events(Request $request): JsonResponse
    {
        $data = $request->validate([
            'start' => ['required', 'date'],
            'end' => ['required', 'date', 'after:start'],
            'types' => ['nullable', 'array'],
            'types.*' => ['in:rooms,reservations,loans'],
            'rooms' => ['nullable', 'array'],
            'rooms.*' => ['integer'],
        ]);

        $from = Carbon::parse($data['start']);
        $to = Carbon::parse($data['end']);
        abort_if($from->diffInDays($to) > 120, 422);

        $types = $data['types'] ?? ['rooms', 'reservations', 'loans'];
        // Raumplanung sieht nur Raumbuchungen
        if (! $request->user()->usesDevices()) {
            $types = array_values(array_intersect($types, ['rooms']));
        }
        $events = [];

        if (in_array('rooms', $types, true)) {
            RoomBooking::query()->with(['room', 'user'])
                ->overlapping($from, $to)
                ->when($data['rooms'] ?? null, fn ($q, $rooms) => $q->whereIn('room_id', $rooms))
                ->get()
                ->each(function (RoomBooking $b) use (&$events, $request) {
                    $allDay = $b->isAllDay();
                    $by = $b->enteredBy();
                    $events[] = [
                        'id' => 'b'.$b->id,
                        'title' => ($b->room?->name ?? '—').' · '.($b->purpose ?? ''),
                        'start' => $allDay ? $b->start_at->toDateString() : $b->start_at->toIso8601String(),
                        'end' => $allDay ? $b->end_at->copy()->addDay()->toDateString() : $b->end_at->toIso8601String(),
                        'allDay' => $allDay,
                        'classNames' => ['ev-room', $b->room?->colorClass() ?? 'room-c0'],
                        'extendedProps' => [
                            // Für die eigene Darstellung im Kalender: Saal, Anlass, Ansprechperson getrennt
                            'room' => $b->room?->name,
                            'headline' => $b->purpose,
                            'contact' => $b->displayName(),
                            'kind' => __('Raumbuchung'),
                            'detail' => __('Ansprechperson: :name', ['name' => $b->displayName()])
                                .($by ? ' · '.__('eingetragen von :name', ['name' => $by]) : ''),
                            'url' => $request->user()->can('update', $b) ? route('bookings.edit', $b) : null,
                        ],
                    ];
                });
        }

        if (in_array('reservations', $types, true)) {
            DeviceReservation::query()->active()->with(['device', 'user'])
                ->overlapping($from, $to)
                ->get()
                ->each(function (DeviceReservation $r) use (&$events) {
                    $events[] = [
                        'id' => 'r'.$r->id,
                        'title' => $r->quantity.'× '.($r->device?->title ?? '—'),
                        'start' => $r->start_at->toIso8601String(),
                        'end' => $r->end_at->toIso8601String(),
                        'classNames' => ['ev-reservation'],
                        'extendedProps' => [
                            'kind' => __('Vormerkung'),
                            'detail' => __('Für :name', ['name' => Names::shown($r->displayName())]).($r->purpose ? ' · '.$r->purpose : ''),
                            'url' => route('devices.show', $r->device_id),
                        ],
                    ];
                });
        }

        if (in_array('loans', $types, true)) {
            DeviceLoan::query()->active()->with('device')
                ->whereDate('loan_start_date', '<', $to)
                ->whereDate('loan_end_date', '>=', $from)
                ->get()
                ->each(function (DeviceLoan $l) use (&$events) {
                    $events[] = [
                        'id' => 'l'.$l->id,
                        'title' => $l->outstanding_quantity.'× '.($l->device?->title ?? '—').' → '.Names::shown($l->borrower_name),
                        'start' => $l->loan_start_date->toDateString(),
                        'end' => $l->loan_end_date->copy()->addDay()->toDateString(),
                        'allDay' => true,
                        'classNames' => [$l->isOverdue() ? 'ev-overdue' : 'ev-loan'],
                        'extendedProps' => [
                            'kind' => $l->isOverdue() ? __('Überfällig') : __('Ausleihe'),
                            'detail' => __('Rückgabe bis :date', ['date' => $l->loan_end_date->format('d.m.Y')]),
                            'url' => route('devices.show', $l->device_id),
                        ],
                    ];
                });
        }

        return response()->json($events);
    }
}
