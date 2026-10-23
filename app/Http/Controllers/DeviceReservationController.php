<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\DeviceReservation;
use App\Services\BookingRules;
use App\Services\LoanService;
use App\Support\Audit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DeviceReservationController extends Controller
{
    public function __construct(
        private BookingRules $rules,
        private LoanService $loans,
    ) {}

    public function create(Device $device): View
    {
        $upcoming = $device->activeReservations()->with('user')->where('end_at', '>', now())->orderBy('start_at')->get();

        return view('reservations.create', compact('device', 'upcoming'));
    }

    public function store(Request $request, Device $device): RedirectResponse
    {
        $data = $request->validate([
            'start_date' => ['required', 'date_format:Y-m-d'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'end_time' => ['required', 'date_format:H:i'],
            'purpose' => ['nullable', 'string', 'max:255'],
            'reserved_by_name' => ['nullable', 'string', 'max:255'],
            'quantity' => ['required', 'integer', 'min:1', 'max:'.$device->total_quantity],
        ], [], [
            'start_date' => __('Startdatum'),
            'end_date' => __('Enddatum'),
            'quantity' => __('Menge'),
            'purpose' => __('Zweck'),
        ]);

        [$start, $end] = $this->rules->period($data['start_date'], $data['start_time'], $data['end_date'], $data['end_time']);

        $reservation = $this->rules->reserveDevice(
            $device, $start, $end, (int) $data['quantity'],
            $data['purpose'] ?? null, $data['reserved_by_name'] ?? null, $request->user(),
        );

        return redirect()->route('devices.show', $device)->with('success', trans_choice(
            ':count Exemplar vorgemerkt.|:count Exemplare vorgemerkt.',
            $reservation->quantity,
            ['count' => $reservation->quantity],
        ));
    }

    public function destroy(Request $request, DeviceReservation $reservation): RedirectResponse
    {
        $this->authorize('cancel', $reservation);

        $reservation->update(['status' => DeviceReservation::STATUS_CANCELLED]);
        Audit::log('device_reservation.cancelled', $reservation);

        return back()->with('success', __('Vormerkung widerrufen.'));
    }

    public function convert(Request $request, DeviceReservation $reservation): RedirectResponse
    {
        $this->authorize('convert', $reservation);

        $data = $request->validate([
            'borrower_name' => ['nullable', 'string', 'max:255'],
            'units' => ['nullable', 'array'],
            'units.*' => ['integer', 'min:1'],
            'accessories' => ['nullable', 'array'],
            'accessories.*' => ['boolean'],
            'accessories_present' => ['nullable', 'boolean'],
        ]);

        $accessories = $request->boolean('accessories_present') ? ($data['accessories'] ?? []) : null;

        $loan = $this->loans->convertReservation(
            $reservation, $request->user(), $data['borrower_name'] ?? null, $data['units'] ?? null, $accessories,
        );

        return redirect()->route('devices.show', $loan->device_id)->with('success', __('Vormerkung in Ausleihe umgewandelt (bis :date).', [
            'date' => $loan->loan_end_date->format('d.m.Y'),
        ]));
    }
}
