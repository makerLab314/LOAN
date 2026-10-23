<?php

namespace App\Http\Controllers;

use App\Models\DeviceLoan;
use App\Models\DeviceReservation;
use App\Models\Room;
use App\Models\RoomBooking;
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
            'room' => ['nullable', 'integer'],
        ]);

        $from = Carbon::parse($data['start']);
        $to = Carbon::parse($data['end']);
        abort_if($from->diffInDays($to) > 120, 422);

        $types = $data['types'] ?? ['rooms', 'reservations', 'loans'];
        $events = [];

        if (in_array('rooms', $types, true)) {
            RoomBooking::query()->with(['room', 'user'])
                ->overlapping($from, $to)
                ->when($data['room'] ?? null, fn ($q, $room) => $q->where('room_id', $room))
                ->get()
                ->each(function (RoomBooking $b) use (&$events, $request) {
                    $events[] = [
                        'id' => 'b'.$b->id,
                        'title' => ($b->room?->name ?? '—').' · '.($b->purpose ?? ''),
                        'start' => $b->start_at->toIso8601String(),
                        'end' => $b->end_at->toIso8601String(),
                        'classNames' => ['ev-room'],
                        'extendedProps' => [
                            'kind' => __('Raumbuchung'),
                            'detail' => __('Gebucht von :name', ['name' => $b->user?->name ?? '—']),
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
                            'detail' => __('Für :name', ['name' => $r->displayName()]).($r->purpose ? ' · '.$r->purpose : ''),
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
                        'title' => $l->outstanding_quantity.'× '.($l->device?->title ?? '—').' → '.$l->borrower_name,
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
