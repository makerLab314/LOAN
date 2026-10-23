<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Device;
use App\Models\DeviceLoan;
use App\Models\DeviceReservation;
use App\Services\LoanService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DeviceLoanController extends Controller
{
    public function __construct(private LoanService $loans) {}

    /**
     * Übersicht: aktive Ausleihen, Vormerkungen, offene Rückgabe-Probleme.
     */
    public function index(Request $request): View
    {
        $loans = DeviceLoan::query()
            ->active()
            ->with(['device.category', 'units', 'loanedBy'])
            ->orderBy('loan_end_date')
            ->get();

        $reservations = DeviceReservation::query()
            ->active()
            ->where('end_at', '>', now()->subDays(1))
            ->with(['device.category', 'user'])
            ->orderBy('start_at')
            ->get();

        $issues = DeviceLoan::query()->withOpenIssues()->with('device')->latest('returned_at')->get();

        $categories = Category::orderBy('name')->get();

        return view('loans.index', compact('loans', 'reservations', 'issues', 'categories'));
    }

    public function history(Request $request): View
    {
        $q = trim((string) $request->query('q'));

        $loans = DeviceLoan::query()
            ->whereNotNull('returned_at')
            ->with(['device', 'returnedBy', 'loanedBy'])
            ->when($q !== '', fn ($query) => $query->where(function ($w) use ($q) {
                $w->where('borrower_name', 'like', '%'.$q.'%')
                    ->orWhereHas('device', fn ($d) => $d->where(fn ($dd) => $dd
                        ->where('title', 'like', '%'.$q.'%')
                        ->orWhere('inventory_number', 'like', '%'.$q.'%')));
            }))
            ->latest('returned_at')
            ->paginate(30)
            ->withQueryString();

        return view('loans.history', compact('loans', 'q'));
    }

    public function store(Request $request, Device $device): RedirectResponse
    {
        $data = $request->validate([
            'borrower_name' => ['required', 'string', 'max:255'],
            'loan_start_date' => ['required', 'date', 'after_or_equal:'.today()->subDays(7)->toDateString()],
            'loan_end_date' => ['required', 'date', 'after_or_equal:loan_start_date'],
            'loan_purpose' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'quantity' => ['required_without:units', 'nullable', 'integer', 'min:1', 'max:9999'],
            'units' => ['nullable', 'array', 'max:9999'],
            'units.*' => ['integer', 'min:1'],
            'accessories' => ['nullable', 'array'],
            'accessories.*' => ['boolean'],
            'accessories_present' => ['nullable', 'boolean'],
        ], [], $this->attributeNames());

        // Checkboxen: nicht angehakte Felder werden nicht übertragen
        if ($request->boolean('accessories_present')) {
            $data['accessories'] = $data['accessories'] ?? [];
        }

        $loan = $this->loans->loan($device, $data, $request->user());

        return redirect()->route('devices.show', $device)->with('success', trans_choice(
            ':count Exemplar an :name verliehen.|:count Exemplare an :name verliehen.',
            $loan->quantity,
            ['count' => $loan->quantity, 'name' => $loan->borrower_name],
        ));
    }

    public function return(Request $request, DeviceLoan $loan): RedirectResponse
    {
        $data = $request->validate([
            'mode' => ['nullable', 'in:all,quantity,units'],
            'quantity' => ['nullable', 'integer', 'min:1'],
            'units' => ['nullable', 'array'],
            'units.*' => ['integer', 'min:1'],
            'accessories' => ['nullable', 'array'],
            'accessories.*' => ['boolean'],
            'accessories_present' => ['nullable', 'boolean'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $mode = $data['mode'] ?? 'all';
        $checks = $request->boolean('accessories_present') ? ($data['accessories'] ?? []) : null;

        $loan = $this->loans->return(
            $loan,
            $request->user(),
            $mode === 'quantity' ? (int) ($data['quantity'] ?? 0) : null,
            $mode === 'units' ? ($data['units'] ?? []) : null,
            $checks,
            $data['note'] ?? null,
        );

        $message = $loan->isActive()
            ? __('Teilrückgabe gebucht – noch offen: :n.', ['n' => $loan->outstanding_quantity])
            : ($loan->hasOpenIssues()
                ? __('Rückgabe gebucht – Zubehör fehlt, wurde vermerkt.')
                : __('Rückgabe gebucht.'));

        return redirect()->to($this->backTo($request, $loan))->with($loan->hasOpenIssues() ? 'warning' : 'success', $message);
    }

    public function extend(Request $request, DeviceLoan $loan): RedirectResponse
    {
        $data = $request->validate([
            'loan_end_date' => ['required', 'date', 'after_or_equal:today'],
        ], [], $this->attributeNames());

        $this->loans->extend($loan, $data['loan_end_date'], $request->user());

        return redirect()->to($this->backTo($request, $loan))->with('success', __('Ausleihe verlängert bis :date.', [
            'date' => $loan->fresh()->loan_end_date->format('d.m.Y'),
        ]));
    }

    public function resolveIssues(Request $request, DeviceLoan $loan): RedirectResponse
    {
        $data = $request->validate(['note' => ['nullable', 'string', 'max:1000']]);

        $this->loans->resolveIssues($loan, $request->user(), $data['note'] ?? null);

        return redirect()->to($this->backTo($request, $loan))->with('success', __('Als erledigt markiert.'));
    }

    private function backTo(Request $request, DeviceLoan $loan): string
    {
        $back = (string) $request->input('redirect');

        // Nur interne Pfade zulassen (kein Open Redirect)
        if ($back !== '' && str_starts_with($back, '/') && ! str_starts_with($back, '//')) {
            return $back;
        }

        return route('devices.show', $loan->device_id);
    }

    /**
     * @return array<string, string>
     */
    private function attributeNames(): array
    {
        return [
            'borrower_name' => __('Ausleihende Person'),
            'loan_start_date' => __('Ausleihdatum'),
            'loan_end_date' => __('Rückgabedatum'),
            'loan_purpose' => __('Zweck'),
            'quantity' => __('Menge'),
        ];
    }
}
