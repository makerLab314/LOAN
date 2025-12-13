<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Device;
use App\Models\DeviceHistory;
use App\Models\DeviceLoan;
use App\Models\DeviceReservation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class DeviceController extends Controller
{
    public function index()
    {
        // Optional: Eager Loading für die Kategorie und aktive Ausleihen
        $devices = Device::with(['category', 'loans'])->get();
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
        $device = Device::findOrFail($request->device_id);
        
        $request->validate([
            'borrower_name'   => 'required|string|max:255',
            'loan_start_date' => 'required|date',
            'loan_end_date'   => 'required|date|after_or_equal:loan_start_date',
            'loan_purpose'    => 'nullable|string|max:255',
            'loan_quantity'   => 'nullable|integer|min:1|max:' . $device->available_quantity,
        ]);

        $loanQuantity = $request->loan_quantity ?? 1;
        
        // Create a new loan record
        DeviceLoan::create([
            'device_id' => $device->id,
            'borrower_name' => $request->borrower_name,
            'quantity' => $loanQuantity,
            'loan_start_date' => $request->loan_start_date,
            'loan_end_date' => $request->loan_end_date,
            'loan_purpose' => $request->loan_purpose,
            'loaned_by' => Auth::id(),
        ]);
        
        // Update loaned quantity
        $device->loaned_quantity = $device->loaned_quantity + $loanQuantity;
        
        // Update status based on availability
        if ($device->loaned_quantity >= $device->total_quantity) {
            $device->status = 'loaned';
        }
        
        // Keep legacy fields for backward compatibility (store last borrower)
        $device->borrower_name = $request->borrower_name;
        $device->loan_start_date = $request->loan_start_date;
        $device->loan_end_date = $request->loan_end_date;
        $device->loan_purpose = $request->loan_purpose;
        $device->save();

        DeviceHistory::create([
            'device_id' => $device->id,
            'action'    => 'loaned',
            'user_name' => $request->borrower_name . ' (' . $loanQuantity . ' Stück)',
            'action_by' => Auth::user()->name,
        ]);

        $message = $loanQuantity === 1 
            ? '1 Gerät wurde erfolgreich verliehen.' 
            : $loanQuantity . ' Geräte wurden erfolgreich verliehen.';
        return redirect()->route('devices.index')->with('status', $message);
    }

    public function returnLoan(Request $request)
    {
        $loan = DeviceLoan::findOrFail($request->loan_id);
        $device = $loan->device;
        
        $returnQuantity = $loan->quantity;
        
        // Update loaned quantity
        $device->loaned_quantity = max(0, $device->loaned_quantity - $returnQuantity);
        
        // Update status based on loaned quantity
        if ($device->loaned_quantity == 0) {
            $device->status = 'available';
            $device->borrower_name = null;
            $device->loan_start_date = null;
            $device->loan_end_date = null;
            $device->loan_purpose = null;
        } else {
            // Update legacy fields with a remaining loan
            $remainingLoan = $device->loans()->where('id', '!=', $loan->id)->first();
            if ($remainingLoan) {
                $device->borrower_name = $remainingLoan->borrower_name;
                $device->loan_start_date = $remainingLoan->loan_start_date;
                $device->loan_end_date = $remainingLoan->loan_end_date;
                $device->loan_purpose = $remainingLoan->loan_purpose;
            }
        }
        $device->save();

        DeviceHistory::create([
            'device_id' => $device->id,
            'action'    => 'returned',
            'user_name' => $loan->borrower_name . ' (' . $returnQuantity . ' Stück)',
            'action_by' => Auth::user()->name,
        ]);

        // Delete the loan record
        $loan->delete();

        $message = $returnQuantity === 1 
            ? '1 Gerät wurde erfolgreich zurückgegeben.' 
            : $returnQuantity . ' Geräte wurden erfolgreich zurückgegeben.';
        return redirect()->route('devices.overview')->with('status', $message);
    }

    public function overview()
    {
        // Get all active loans with device info
        $loans = DeviceLoan::with(['device.category'])
            ->orderBy('loan_end_date')
            ->get();

        // Vormerkungen inkl. Device/Kategorie
        $reservations = DeviceReservation::query()
            ->with(['device:id,title,group,image,description', 'user:id,name'])
            ->where('status', '!=', 'cancelled')
            ->orderBy('start_at')
            ->get();

        return view('devices.overview', compact('loans', 'reservations'));
    }

    public function create()
    {
        $categories = Category::orderBy('name')->get();
        return view('devices.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'          => ['required','string','max:255'],
            'description'    => ['nullable','string'],
            'image'          => ['nullable','image'],
            'category_id'    => ['required','exists:categories,id'],
            'total_quantity' => ['nullable','integer','min:1'],
        ]);

        // group SPIEGELN (Übergangsphase, bis Spalte entfernt wird)
        $category = Category::find($validated['category_id']);
        $payload = [
            'title'          => $validated['title'],
            'description'    => $validated['description'] ?? null,
            'category_id'    => $validated['category_id'],
            'group'          => $category?->name, // hält Altlogik kompatibel
            'total_quantity' => $validated['total_quantity'] ?? 1,
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
            'title'          => ['required','string','max:255'],
            'description'    => ['nullable','string'],
            'image'          => ['nullable','image'],
            'category_id'    => ['required','exists:categories,id'],
            'total_quantity' => ['nullable','integer','min:1'],
        ]);

        $category = Category::find($validated['category_id']);
        $payload = [
            'title'          => $validated['title'],
            'description'    => $validated['description'] ?? null,
            'category_id'    => $validated['category_id'],
            'group'          => $category?->name, // Übergangskompatibilität
            'total_quantity' => $validated['total_quantity'] ?? 1,
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
