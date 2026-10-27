<?php

namespace App\Http\Controllers;

use App\Models\Room;
use App\Models\RoomBooking;
use App\Services\BookingRules;
use App\Support\Audit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RoomBookingController extends Controller
{
    public function __construct(private BookingRules $rules) {}

    public function index(Request $request): View
    {
        return $this->list($request, archived: false);
    }

    public function archived(Request $request): View
    {
        return $this->list($request, archived: true);
    }

    private function list(Request $request, bool $archived): View
    {
        $rooms = Room::orderBy('name')->get();
        $roomId = $request->integer('room') ?: null;

        $bookings = RoomBooking::query()
            ->with(['room', 'user'])
            ->when($archived, fn ($q) => $q->past()->latest('start_at'), fn ($q) => $q->upcoming()->orderBy('start_at'))
            ->when($roomId, fn ($q) => $q->where('room_id', $roomId))
            ->paginate(40)
            ->withQueryString();

        return view('bookings.index', compact('rooms', 'bookings', 'roomId', 'archived'));
    }

    /**
     * „Neue Buchung“: direkt ins Formular mit dem ersten Raum – der Raum lässt sich dort wechseln.
     */
    public function start(Request $request): RedirectResponse
    {
        $room = Room::orderBy('name')->first();
        if (! $room) {
            return redirect()->route('rooms.index')->with('error', __('Es gibt noch keine Räume.'));
        }

        return redirect()->route('bookings.create', array_filter(['room' => $room->id, 'date' => $request->query('date')]));
    }

    public function create(Request $request, Room $room): View
    {
        return view('bookings.form', [
            'room' => $room,
            'booking' => new RoomBooking,
            'rooms' => Room::orderBy('name')->get(),
            'date' => preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $request->query('date')) ? $request->query('date') : null,
        ]);
    }

    public function store(Request $request, Room $room): RedirectResponse
    {
        $data = $this->validated($request);
        // Im Formular lässt sich der Raum noch wechseln (z. B. auf einen freien Saal)
        $room = isset($data['room_id']) ? Room::findOrFail($data['room_id']) : $room;
        [$start, $end] = $this->rules->period($data['start_date'], $data['start_time'], $data['end_date'], $data['end_time']);

        $this->rules->bookRoom($room, $start, $end, $data['purpose'], $request->user(), bookedFor: $data['booked_by_name'] ?? null);

        return redirect()->route('bookings.index')->with('success', __(':room gebucht.', ['room' => $room->name]));
    }

    public function edit(RoomBooking $booking): View
    {
        $this->authorize('update', $booking);

        return view('bookings.form', [
            'room' => $booking->room,
            'booking' => $booking,
            'rooms' => Room::orderBy('name')->get(),
            'date' => null,
        ]);
    }

    public function update(Request $request, RoomBooking $booking): RedirectResponse
    {
        $this->authorize('update', $booking);

        $data = $this->validated($request);
        [$start, $end] = $this->rules->period($data['start_date'], $data['start_time'], $data['end_date'], $data['end_time']);
        $room = isset($data['room_id']) ? Room::findOrFail($data['room_id']) : $booking->room;

        $this->rules->bookRoom($room, $start, $end, $data['purpose'], $request->user(), $booking, $data['booked_by_name'] ?? null);

        return redirect()->route('bookings.index')->with('success', __('Buchung gespeichert.'));
    }

    public function destroy(RoomBooking $booking): RedirectResponse
    {
        $this->authorize('delete', $booking);

        $booking->delete();
        Audit::log('room_booking.deleted', $booking);

        return back()->with('success', __('Buchung storniert.'));
    }

    /**
     * Live-Prüfung im Buchungsformular: Konflikte, freie Säle und die Belegung des Tages.
     */
    public function availability(Request $request): JsonResponse
    {
        $data = $request->validate([
            'room_id' => ['required', 'integer'],
            'start_date' => ['required', 'date_format:Y-m-d'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_date' => ['required', 'date_format:Y-m-d'],
            'end_time' => ['required', 'date_format:H:i'],
            'ignore' => ['nullable', 'integer'],
        ]);

        $rooms = Room::orderBy('name')->get();
        $room = $rooms->firstWhere('id', $data['room_id']) ?? abort(404);
        $ignore = $data['ignore'] ?? null;
        $problem = null;
        $conflicts = collect();
        $free = [];

        try {
            [$start, $end] = $this->rules->period($data['start_date'], $data['start_time'], $data['end_date'], $data['end_time']);
            $this->rules->assertRoomPeriod($start, $end, $ignore ? RoomBooking::find($ignore) : null);
        } catch (ValidationException $e) {
            $problem = collect($e->errors())->flatten()->first();
        }

        if (isset($start, $end)) {
            $conflicts = $this->rules->roomConflicts($room, $start, $end, $ignore);
            if ($conflicts->isNotEmpty()) {
                $free = $rooms->where('id', '!=', $room->id)
                    ->filter(fn (Room $r) => $this->rules->roomConflicts($r, $start, $end, $ignore)->isEmpty())
                    ->map(fn (Room $r) => ['id' => $r->id, 'name' => $r->name])
                    ->values();
            }
        }

        // Tagesübersicht aller Säle am Starttag (Minuten ab 00:00)
        $day = Carbon::createFromFormat('Y-m-d', $data['start_date'])->startOfDay();
        $minutes = fn (Carbon $t) => (int) max(0, min(1440, $day->diffInMinutes($t, false)));
        $dayBookings = RoomBooking::query()->with('user')
            ->overlapping($day, $day->copy()->addDay())
            ->when($ignore, fn ($q) => $q->where('id', '!=', $ignore))
            ->orderBy('start_at')
            ->get()
            ->groupBy('room_id');

        return response()->json([
            'problem' => $problem,
            'conflicts' => $conflicts->map(fn (RoomBooking $b) => [
                'date' => $b->start_at->translatedFormat('D d.m.'),
                'time' => $b->timeRange(),
                'purpose' => $b->purpose,
                'name' => $b->displayName(),
            ])->values(),
            'free' => $free,
            'day' => [
                'label' => $day->translatedFormat('l, d.m.Y'),
                'rooms' => $rooms->map(fn (Room $r) => [
                    'id' => $r->id,
                    'name' => $r->name,
                    'color' => $r->colorClass(),
                    'bookings' => $dayBookings->get($r->id, collect())->map(fn (RoomBooking $b) => [
                        'from' => $minutes($b->start_at),
                        'to' => $minutes($b->end_at),
                        'time' => $b->timeRange(),
                        'title' => collect([$b->purpose, $b->displayName()])->filter()->implode(' · '),
                    ])->values(),
                ])->values(),
                'selection' => isset($start, $end) ? ['from' => $minutes($start), 'to' => $minutes($end)] : null,
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'room_id' => ['nullable', Rule::exists('rooms', 'id')->whereNull('deleted_at')],
            'start_date' => ['required', 'date_format:Y-m-d'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'end_time' => ['required', 'date_format:H:i'],
            'purpose' => ['required', 'string', 'max:255'],
            // Am Kiosk bucht ein gemeinsames Konto – deshalb muss dort ein Name dabei sein
            'booked_by_name' => [$request->user()->isKiosk() ? 'required' : 'nullable', 'string', 'max:255'],
        ], [], [
            'start_date' => __('Startdatum'),
            'start_time' => __('Startzeit'),
            'end_date' => __('Enddatum'),
            'end_time' => __('Endzeit'),
            'purpose' => __('Wofür / für wen'),
            'booked_by_name' => $request->user()->isKiosk() ? __('Name') : __('Ansprechperson'),
        ]);
    }
}
