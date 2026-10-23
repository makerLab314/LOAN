<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Device;
use App\Models\DeviceAccessory;
use App\Models\DeviceLoan;
use App\Services\ImageStore;
use App\Services\InventoryNumbers;
use App\Support\Audit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DeviceController extends Controller
{
    public function __construct(
        private ImageStore $images,
        private InventoryNumbers $numbers,
    ) {}

    public function index(Request $request): View
    {
        $devices = Device::query()
            ->withLoanedQuantity()
            ->with('category')
            ->withCount(['activeReservations as upcoming_reservations_count'])
            ->orderBy('title')
            ->get();

        $categories = Category::orderBy('name')->get();

        return view('devices.index', compact('devices', 'categories'));
    }

    public function show(Request $request, Device $device): View
    {
        $device->load(['category', 'accessories']);

        $activeLoans = $device->activeLoans()->with(['units', 'loanedBy'])->orderBy('loan_end_date')->get();
        $device->setRelation('activeLoans', $activeLoans);

        $reservations = $device->activeReservations()->with('user')->orderBy('start_at')->get();
        $openIssues = $device->loans()->withOpenIssues()->latest('returned_at')->get();

        $history = AuditLog::query()
            ->where('subject_type', 'device')->where('subject_id', $device->id)
            ->orWhere(function ($q) use ($device) {
                $q->where('subject_type', 'loan')
                    ->whereIn('subject_id', $device->loans()->select('id'));
            })
            ->latest('created_at')->latest('id')
            ->limit(30)
            ->get();

        $loanedUnits = $device->loanedUnits();
        $preselectUnit = $request->integer('unit') ?: null;
        if ($preselectUnit && ($preselectUnit < 1 || $preselectUnit > $device->total_quantity)) {
            $preselectUnit = null;
        }

        // Vorschläge für das Namensfeld (zuletzt verwendete Namen)
        $borrowers = DeviceLoan::query()->latest('id')->limit(300)->pluck('borrower_name')->unique()->take(80)->values();

        return view('devices.show', compact(
            'device', 'activeLoans', 'reservations', 'openIssues', 'history', 'loanedUnits', 'preselectUnit', 'borrowers',
        ));
    }

    public function create(): View
    {
        return view('devices.form', [
            'device' => new Device(['total_quantity' => 1]),
            'categories' => Category::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $category = Category::find($data['category_id']);

        $device = DB::transaction(function () use ($request, $data, $category) {
            $device = Device::create([
                'inventory_number' => ($data['inventory_number'] ?? null) ?: $this->numbers->next($category),
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'category_id' => $data['category_id'],
                'total_quantity' => $data['total_quantity'],
                'image' => $request->hasFile('image') ? $this->images->store($request->file('image')) : null,
            ]);
            $this->syncAccessories($device, $data['accessories'] ?? []);

            return $device;
        });

        Audit::log('device.created', $device, ['quantity' => $device->total_quantity]);

        return redirect()->route('devices.show', $device)->with('success', __('Gerät angelegt.'));
    }

    public function edit(Device $device): View
    {
        $device->load('accessories');

        return view('devices.form', [
            'device' => $device,
            'categories' => Category::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Device $device): RedirectResponse
    {
        $data = $this->validated($request, $device);

        $loaned = $device->loaned_quantity;
        if ($data['total_quantity'] < $loaned) {
            return back()->withInput()->withErrors(['total_quantity' => __('Es sind gerade :n Exemplare verliehen – die Gesamtmenge kann nicht kleiner sein.', ['n' => $loaned])]);
        }

        $before = $device->only(['inventory_number', 'title', 'category_id', 'total_quantity']);

        DB::transaction(function () use ($request, $data, $device) {
            $payload = [
                'inventory_number' => ($data['inventory_number'] ?? null) ?: ($device->inventory_number ?: $this->numbers->next(Category::find($data['category_id']))),
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'category_id' => $data['category_id'],
                'total_quantity' => $data['total_quantity'],
            ];

            if ($request->hasFile('image')) {
                $this->images->delete($device->image);
                $payload['image'] = $this->images->store($request->file('image'));
            } elseif ($request->boolean('remove_image')) {
                $this->images->delete($device->image);
                $payload['image'] = null;
            }

            $device->update($payload);
            $this->syncAccessories($device, $data['accessories'] ?? []);
        });

        $changes = array_diff_assoc(array_map('strval', $device->only(array_keys($before))), array_map('strval', $before));
        Audit::log('device.updated', $device, $changes ? ['changed' => array_keys($changes)] : []);

        return redirect()->route('devices.show', $device)->with('success', __('Gerät gespeichert.'));
    }

    public function destroy(Device $device): RedirectResponse
    {
        if ($device->activeLoans()->exists()) {
            return back()->with('error', __('Das Gerät ist noch verliehen und kann nicht gelöscht werden.'));
        }

        $device->activeReservations()->update(['status' => 'cancelled']);
        $device->delete(); // Soft Delete – Historie bleibt erhalten

        Audit::log('device.deleted', $device);

        return redirect()->route('devices.index')->with('success', __('Gerät gelöscht.'));
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Device $device = null): array
    {
        $data = $request->validate([
            'inventory_number' => ['nullable', 'string', 'max:32', 'regex:/^[A-Za-z0-9][A-Za-z0-9._-]*$/',
                Rule::unique('devices', 'inventory_number')->ignore($device?->id)],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240', 'dimensions:max_width=8000,max_height=8000'],
            'remove_image' => ['nullable', 'boolean'],
            'category_id' => ['required', Rule::exists('categories', 'id')->whereNull('deleted_at')],
            'total_quantity' => ['required', 'integer', 'min:1', 'max:9999'],
            'accessories' => ['nullable', 'array', 'max:50'],
            'accessories.*.name' => ['nullable', 'string', 'max:255'],
            'accessories.*.quantity' => ['nullable', 'integer', 'min:1', 'max:999'],
        ], [], [
            'inventory_number' => __('Inventar-ID'),
            'title' => __('Bezeichnung'),
            'total_quantity' => __('Anzahl'),
            'category_id' => __('Kategorie'),
        ]);

        if (! empty($data['inventory_number'])) {
            $data['inventory_number'] = strtoupper($data['inventory_number']);
        }

        return $data;
    }

    /**
     * @param  array<int, array{name?: string|null, quantity?: int|null}>  $rows
     */
    private function syncAccessories(Device $device, array $rows): void
    {
        $device->accessories()->delete();

        $position = 0;
        foreach ($rows as $row) {
            $name = trim((string) ($row['name'] ?? ''));
            if ($name === '') {
                continue;
            }
            DeviceAccessory::create([
                'device_id' => $device->id,
                'name' => $name,
                'quantity' => max(1, (int) ($row['quantity'] ?? 1)),
                'position' => $position++,
            ]);
        }
    }
}
