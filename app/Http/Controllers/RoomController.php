<?php

namespace App\Http\Controllers;

use App\Models\Room;
use App\Models\RoomBooking;
use App\Support\Audit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RoomController extends Controller
{
    public function index(): View
    {
        $rooms = Room::query()
            ->withCount(['bookings as upcoming_count' => fn ($q) => $q->upcoming()])
            ->with(['bookings' => fn ($q) => $q->where('start_at', '<=', now())->where('end_at', '>', now())->with('user')])
            ->orderBy('name')
            ->get();

        return view('rooms.index', compact('rooms'));
    }

    public function create(): View
    {
        return view('rooms.form', ['room' => new Room]);
    }

    public function store(Request $request): RedirectResponse
    {
        $room = Room::create($this->validated($request));
        Audit::log('room.created', $room);

        return redirect()->route('rooms.index')->with('success', __('Raum angelegt.'));
    }

    public function edit(Room $room): View
    {
        return view('rooms.form', compact('room'));
    }

    public function update(Request $request, Room $room): RedirectResponse
    {
        $room->update($this->validated($request));
        Audit::log('room.updated', $room);

        return redirect()->route('rooms.index')->with('success', __('Raum gespeichert.'));
    }

    public function destroy(Room $room): RedirectResponse
    {
        $upcoming = RoomBooking::where('room_id', $room->id)->upcoming()->count();
        if ($upcoming > 0) {
            return back()->with('error', trans_choice(
                'Der Raum hat noch :count anstehende Buchung.|Der Raum hat noch :count anstehende Buchungen.',
                $upcoming, ['count' => $upcoming],
            ));
        }

        $room->delete();
        Audit::log('room.deleted', $room);

        return redirect()->route('rooms.index')->with('success', __('Raum gelöscht.'));
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'location' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
        ], [], [
            'name' => __('Name'),
            'location' => __('Ort'),
            'description' => __('Beschreibung'),
        ]);
    }
}
