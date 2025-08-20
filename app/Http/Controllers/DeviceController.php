<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Device;
use App\Models\DeviceHistory;
use App\Models\DeviceReservation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class DeviceController extends Controller
{
    public function index()
    {
        // Optional: Eager Loading für die Kategorie
        $devices = Device::with('category')->get();
        return view('devices.index', compact('devices'));
    }

    public function show(Device $device)
    {
        $histories = DeviceHistory::where('device_id', $device->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return view('devices.show', compact('device', 'histories'));
    }

    public function loan(Request $request)
    {
        $request->validate([
            'borrower_name'   => 'required|string|max:255',
            'loan_start_date' => 'required|date',
            'loan_end_date'   => 'required|date|after_or_equal:loan_start_date',
            'loan_purpose'    => 'nullable|string|max:255',
        ]);

        $device = Device::findOrFail($request->device_id);
        $device->status = 'loaned';
        $device->borrower_name = $request->borrower_name;
        $device->loan_start_date = $request->loan_start_date;
        $device->loan_end_date = $request->loan_end_date;
        $device->loan_purpose    = $request->loan_purpose;
        $device->save();

        DeviceHistory::create([
            'device_id' => $device->id,
            'action'    => 'loaned',
            'user_name' => $request->borrower_name,
            'action_by' => Auth::user()->name,
        ]);

        return redirect()->route('devices.index')->with('status', 'Das Gerät wurde erfolgreich verliehen.');
    }

    public function return(Request $request)
    {
        $device = Device::findOrFail($request->device_id);
        $device->status = 'available';
        $device->borrower_name = null;
        $device->loan_start_date = null;
        $device->loan_end_date = null;
        $device->loan_purpose = null;
        $device->save();

        DeviceHistory::create([
            'device_id' => $device->id,
            'action'    => 'returned',
            'user_name' => Auth::user()->name,
            'action_by' => Auth::user()->name,
        ]);

        return redirect()->route('devices.index')->with('status', 'Das Gerät wurde erfolgreich zurückgegeben.');
    }

    public function overview()
    {
        // Geräte, die ausgeliehen sind (Reihenfolge bleibt kompatibel)
        $devices = Device::query()
            ->where('status', 'loaned')
            ->orderBy('group')
            ->orderBy('title')
            ->get();

        // Vormerkungen inkl. Device/Kategorie
        $reservations = DeviceReservation::query()
            ->with(['device:id,title,group,image,description', 'user:id,name'])
            ->where('status', '!=', 'cancelled')
            ->orderBy('start_at')
            ->get();

        return view('devices.overview', compact('devices', 'reservations'));
    }

    public function create()
    {
        $categories = Category::orderBy('name')->get();
        return view('devices.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'       => ['required','string','max:255'],
            'description' => ['nullable','string'],
            'image'       => ['nullable','image'],
            'category_id' => ['required','exists:categories,id'],
        ]);

        // group SPIEGELN (Übergangsphase, bis Spalte entfernt wird)
        $category = Category::find($validated['category_id']);
        $payload = [
            'title'       => $validated['title'],
            'description' => $validated['description'] ?? null,
            'category_id' => $validated['category_id'],
            'group'       => $category?->name, // hält Altlogik kompatibel
        ];

        if ($request->hasFile('image')) {
            $payload['image'] = $request->file('image')->store('images', 'public');
        }

        Device::create($payload);

        return redirect()->route('devices.index')->with('success', 'Gerät erfolgreich hinzugefügt.');
    }

    public function edit(Device $device)
    {
        $categories = Category::orderBy('name')->get();
        return view('devices.edit', compact('device', 'categories'));
    }

    public function update(Request $request, Device $device)
    {
        $validated = $request->validate([
            'title'       => ['required','string','max:255'],
            'description' => ['nullable','string'],
            'image'       => ['nullable','image'],
            'category_id' => ['required','exists:categories,id'],
        ]);

        $category = Category::find($validated['category_id']);
        $payload = [
            'title'       => $validated['title'],
            'description' => $validated['description'] ?? null,
            'category_id' => $validated['category_id'],
            'group'       => $category?->name, // Übergangskompatibilität
        ];

        if ($request->hasFile('image')) {
            // altes Bild löschen (falls vorhanden)
            if ($device->image) {
                Storage::disk('public')->delete($device->image);
            }
            $payload['image'] = $request->file('image')->store('images', 'public');
        }

        $device->update($payload);

        return redirect()->route('devices.index')->with('success', 'Gerät erfolgreich aktualisiert.');
    }

    public function destroy(Device $device)
    {
        if ($device->image) {
            Storage::disk('public')->delete($device->image);
        }

        $device->delete();

        return redirect()->route('devices.index')->with('success', 'Gerät erfolgreich gelöscht.');
    }
}
