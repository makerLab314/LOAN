<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\DeviceReservation;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class DeviceReservationController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    // Formular anzeigen (wie bei Räumen, nur für Devices)
    public function create(Device $device)
    {
        return view('devices.reservations.create', [
            'device' => $device
        ]);
    }

    // Vormerkung speichern
    public function store(Request $request, Device $device)
    {
        // 1) Basiseingaben prüfen
        $validated = $request->validate([
            'start_date' => ['required','date'],
            'start_time' => ['required','date_format:H:i'],
            'end_date'   => ['required','date','after_or_equal:start_date'],
            'end_time'   => ['required','date_format:H:i'],
            'purpose'    => ['nullable','string','max:255'],
            'reserved_by_name' => ['nullable','string','max:255'],
        ]);

        $start = Carbon::createFromFormat('Y-m-d H:i', $validated['start_date'].' '.$validated['start_time']);
        $end   = Carbon::createFromFormat('Y-m-d H:i', $validated['end_date'].' '.$validated['end_time']);

        if ($end->lessThanOrEqualTo($start)) {
            return back()->withErrors(['end_time' => 'Ende muss nach dem Start liegen.'])
                         ->withInput();
        }

        // 2) Wortlimit (<= 100 Wörter) serverseitig erzwingen
        $wordCount = str_word_count((string)($validated['purpose'] ?? ''), 0, 'ÄÖÜäöüß');
        if ($wordCount > 100) {
            return back()->withErrors(['purpose' => 'Bitte höchstens 100 Wörter. (Aktuell: '.$wordCount.')'])
                         ->withInput();
        }

        // 3) Terminüberschneidungen prüfen (keine Überschneidung mit bestehenden aktiven Vormerkungen)
        $overlaps = $device->reservations()
            ->where('status', '!=', 'cancelled')
            ->where(function ($q) use ($start, $end) {
                $q->whereBetween('start_at', [$start, $end])
                  ->orWhereBetween('end_at', [$start, $end])
                  ->orWhere(function ($q2) use ($start, $end) {
                      $q2->where('start_at', '<=', $start)
                         ->where('end_at', '>=', $end);
                  });
            })
            ->exists();

        if ($overlaps) {
            return back()->withErrors(['start_date' => 'Dieser Zeitraum überschneidet sich mit einer bestehenden Vormerkung.'])
                         ->withInput();
        }

        // 4) Vormerkung anlegen
        DeviceReservation::create([
            'device_id' => $device->id,
            'user_id'   => Auth::id(),
            'start_at'  => $start,
            'end_at'    => $end,
            'purpose'   => $validated['purpose'] ?? null,
            'reserved_by_name' => $request->input('reserved_by_name'),
            'status'    => 'pending',
        ]);

        return redirect()
            ->route('devices.show', $device) // passe an, falls deine Show‑Route anders heißt
            ->with('success', 'Gerät wurde erfolgreich vorgemerkt.');
    }
    public function destroy(DeviceReservation $reservation)
    {
        // Nur der Ersteller darf seine Vormerkung widerrufen
        if ($reservation->user_id !== auth()->id()) {
            abort(403, 'Unbefugt');
        }

        // Entweder löschen...
        // $reservation->delete();

        // ...oder Status auf "cancelled" setzen (empfohlen, falls du Historie behalten willst):
        $reservation->update(['status' => 'cancelled']);

        return back()->with('status', 'Die Vormerkung wurde widerrufen.');
    }

}

