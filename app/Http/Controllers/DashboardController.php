<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\DeviceLoan;
use App\Models\DeviceReservation;
use App\Models\Room;
use App\Models\RoomBooking;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        if (! $request->user()->usesDevices()) {
            return $this->rooms($request);
        }

        $activeLoans = DeviceLoan::query()->active()->with('device')->orderBy('loan_end_date')->get();

        $overdue = $activeLoans->filter->isOverdue()->values();
        $dueToday = $activeLoans->filter->isDueToday()->values();
        $dueSoon = $activeLoans->filter(fn (DeviceLoan $l) => $l->loan_end_date->between(today()->addDay(), today()->addDays(3)))->values();

        $issues = DeviceLoan::query()->withOpenIssues()->with('device')->latest('returned_at')->limit(10)->get();

        $reservations = DeviceReservation::query()
            ->active()
            ->with(['device', 'user'])
            ->where('end_at', '>', now())
            ->where('start_at', '<', today()->addDays(7))
            ->orderBy('start_at')
            ->limit(12)
            ->get();

        $bookings = RoomBooking::query()
            ->with(['room', 'user'])
            ->where('end_at', '>', now())
            ->where('start_at', '<', today()->addDays(2))
            ->orderBy('start_at')
            ->limit(12)
            ->get();

        $stats = [
            'devices' => Device::count(),
            'units' => (int) Device::sum('total_quantity'),
            'loaned' => $activeLoans->sum(fn (DeviceLoan $l) => $l->outstanding_quantity),
            'overdue' => $overdue->count(),
            'due_today' => $dueToday->count(),
            'issues' => DeviceLoan::query()->withOpenIssues()->count(),
        ];

        $myReservations = DeviceReservation::query()->active()->with('device')
            ->where('user_id', $request->user()->id)->where('end_at', '>', now())
            ->orderBy('start_at')->limit(5)->get();

        return view('dashboard', compact(
            'overdue', 'dueToday', 'dueSoon', 'issues', 'reservations', 'bookings', 'stats', 'myReservations',
        ));
    }

    /**
     * Startseite der Raumplanung: Buchen und Kalender, dazu die Belegung – keine Geräte.
     */
    private function rooms(Request $request): View
    {
        $rooms = Room::query()
            ->with(['bookings' => fn ($q) => $q->where('start_at', '<=', now())->where('end_at', '>', now())->with('user')])
            ->orderBy('name')
            ->get();

        $today = RoomBooking::query()->with(['room', 'user'])
            ->overlapping(today(), today()->addDay())
            ->orderBy('start_at')
            ->get();

        $upcoming = RoomBooking::query()->with(['room', 'user'])
            ->where('start_at', '>=', today()->addDay())
            ->where('start_at', '<', today()->addDays(8))
            ->orderBy('start_at')
            ->limit(20)
            ->get()
            ->groupBy(fn (RoomBooking $b) => $b->start_at->toDateString());

        $mine = RoomBooking::query()->upcoming()->with('room')
            ->where('user_id', $request->user()->id)
            ->orderBy('start_at')->limit(5)->get();

        return view('dashboard-rooms', compact('rooms', 'today', 'upcoming', 'mine'));
    }
}
