<?php

namespace App\Http\Controllers;

use App\Models\Room;
use App\Models\RoomBooking;
use App\Services\BookingRules;
use App\Support\Audit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
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

    public function create(Request $request, Room $room): View
    {
        $upcoming = $room->bookings()->upcoming()->with('user')->orderBy('start_at')->limit(15)->get();

        return view('bookings.form', [
            'room' => $room,
            'booking' => new RoomBooking,
            'rooms' => Room::orderBy('name')->get(),
            'upcoming' => $upcoming,
            'date' => preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $request->query('date')) ? $request->query('date') : null,
        ]);
    }

    public function store(Request $request, Room $room): RedirectResponse
    {
        $data = $this->validated($request);
        [$start, $end] = $this->rules->period($data['start_date'], $data['start_time'], $data['end_date'], $data['end_time']);

        $this->rules->bookRoom($room, $start, $end, $data['purpose'], $request->user());

        return redirect()->route('bookings.index')->with('success', __('Raum gebucht.'));
    }

    public function edit(RoomBooking $booking): View
    {
        $this->authorize('update', $booking);

        $upcoming = $booking->room->bookings()->upcoming()->with('user')
            ->where('id', '!=', $booking->id)->orderBy('start_at')->limit(15)->get();

        return view('bookings.form', [
            'room' => $booking->room,
            'booking' => $booking,
            'rooms' => Room::orderBy('name')->get(),
            'upcoming' => $upcoming,
            'date' => null,
        ]);
    }

    public function update(Request $request, RoomBooking $booking): RedirectResponse
    {
        $this->authorize('update', $booking);

        $data = $this->validated($request, withRoom: true);
        [$start, $end] = $this->rules->period($data['start_date'], $data['start_time'], $data['end_date'], $data['end_time']);
        $room = Room::findOrFail($data['room_id']);

        $this->rules->bookRoom($room, $start, $end, $data['purpose'], $request->user(), $booking);

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
     * @return array<string, mixed>
     */
    private function validated(Request $request, bool $withRoom = false): array
    {
        return $request->validate(array_filter([
            'room_id' => $withRoom ? ['required', Rule::exists('rooms', 'id')->whereNull('deleted_at')] : null,
            'start_date' => ['required', 'date_format:Y-m-d'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'end_time' => ['required', 'date_format:H:i'],
            'purpose' => ['required', 'string', 'max:255'],
        ]), [], [
            'start_date' => __('Startdatum'),
            'start_time' => __('Startzeit'),
            'end_date' => __('Enddatum'),
            'end_time' => __('Endzeit'),
            'purpose' => __('Zweck'),
        ]);
    }
}
