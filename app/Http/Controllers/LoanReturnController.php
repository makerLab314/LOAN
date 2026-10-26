<?php

namespace App\Http\Controllers;

use App\Models\DeviceLoan;
use App\Services\InventoryNumbers;
use App\Services\LoanService;
use App\Services\ReturnScans;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Rückgabe per Scan: Etiketten scannen (oder IDs eintippen), die passenden
 * Ausleihen erscheinen, dann alles auf einmal zurückbuchen.
 * Am Kiosk der einzige Weg zur Rückgabe (siehe ReturnScans).
 */
class LoanReturnController extends Controller
{
    public const MAX_LINES = 50;

    public function __construct(
        private LoanService $loans,
        private InventoryNumbers $numbers,
        private ReturnScans $scans,
    ) {}

    public function create(Request $request): View
    {
        // Nach Validierungsfehlern die erfassten Ausleihen wieder anzeigen
        $old = collect((array) old('returns', []))->filter(fn ($r) => is_array($r) && isset($r['loan_id']))->take(self::MAX_LINES);
        $initial = DeviceLoan::query()->active()->with(['device', 'units'])
            ->whereKey($old->pluck('loan_id')->map(fn ($id) => (int) $id)->all())
            ->get()
            ->map(function (DeviceLoan $loan) use ($old) {
                $line = $old->firstWhere('loan_id', $loan->id) ?? [];

                return [
                    'loan' => $this->payload($loan),
                    'device' => ['id' => $loan->device_id, 'title' => $loan->device?->title],
                    'units' => array_values(array_intersect(array_map('intval', (array) ($line['units'] ?? [])), $loan->outstandingUnitNumbers())),
                    'quantity' => (int) ($line['quantity'] ?? $loan->outstanding_quantity),
                ];
            })->values();

        return view('loans.return', compact('initial'));
    }

    /**
     * Gescannter/eingegebener Code → aktive Ausleihen dieses Geräts.
     * Merkt sich den Scan als Nachweis für die Kiosk-Rückgabe.
     */
    public function scan(Request $request): JsonResponse
    {
        $code = (string) $request->validate(['code' => ['required', 'string', 'max:200']])['code'];
        $hit = $this->numbers->resolve($code);

        if (! $hit) {
            return response()->json(['found' => false, 'message' => __('Keine Inventar-ID „:code“ gefunden.', ['code' => mb_substr($code, 0, 40)])], 404);
        }

        $device = $hit['device'];
        $this->scans->record($device, $hit['unit']);

        $loans = $device->activeLoans()->with('units')->orderBy('loan_end_date')->get();

        return response()->json([
            'found' => true,
            'device' => ['id' => $device->id, 'title' => $device->title],
            'unit' => $hit['unit'],
            'label' => $hit['unit'] ? $device->unitLabel($hit['unit']) : ($device->inventory_number ?? 'ID'.$device->id),
            'loans' => $loans->map(fn (DeviceLoan $l) => $this->payload($l->setRelation('device', $device)))->values(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'returns' => ['required', 'array', 'min:1', 'max:'.self::MAX_LINES],
            'returns.*' => ['array'],
            'returns.*.loan_id' => ['required', 'integer', 'distinct', Rule::exists('device_loans', 'id')],
            'returns.*.quantity' => ['nullable', 'integer', 'min:1', 'max:9999'],
            'returns.*.units' => ['nullable', 'array', 'max:9999'],
            'returns.*.units.*' => ['integer', 'min:1', 'max:9999'],
            'returns.*.accessories' => ['nullable', 'array'],
            'returns.*.accessories.*' => ['boolean'],
            'returns.*.accessories_present' => ['nullable', 'boolean'],
            'note' => ['nullable', 'string', 'max:1000'],
        ], [
            'returns.required' => __('Bitte mindestens ein Gerät scannen.'),
        ]);

        $user = $request->user();
        $lines = collect($data['returns'])->sortBy('loan_id')->values();
        $kiosk = $user->isKiosk();

        $done = DB::transaction(function () use ($lines, $user, $kiosk, $data) {
            $done = [];
            foreach ($lines as $line) {
                $loan = DeviceLoan::query()->with(['device', 'units'])->findOrFail((int) $line['loan_id']);
                $units = ! empty($line['units']) ? array_map('intval', $line['units']) : null;

                try {
                    // Kiosk: nur, was gescannt wurde (Exemplare einzeln, sonst das Gerät)
                    if ($kiosk) {
                        $units = $this->scans->authorize($loan, $units);
                    }

                    $loan = $this->loans->return(
                        $loan,
                        $user,
                        $units ? null : (isset($line['quantity']) ? (int) $line['quantity'] : null),
                        $units,
                        ! empty($line['accessories_present']) ? ($line['accessories'] ?? []) : null,
                        $data['note'] ?? null,
                    );
                } catch (ValidationException $e) {
                    throw ValidationException::withMessages(['returns' => ($loan->device?->title ?? '—').' · '.$loan->borrower_name.': '.collect($e->errors())->flatten()->first()]);
                }

                $done[] = [$loan, $units];
            }

            return $done;
        });

        foreach ($done as [$loan, $units]) {
            $this->scans->consume($loan, $units);
        }

        $issues = collect($done)->contains(fn ($d) => $d[0]->hasOpenIssues());
        $count = count($done);

        return redirect()->route('loans.return-scan')->with($issues ? 'warning' : 'success', $issues
            ? __('Rückgabe gebucht – Zubehör fehlt, wurde vermerkt.')
            : trans_choice(':count Rückgabe gebucht.|:count Rückgaben gebucht.', $count, ['count' => $count]));
    }

    /**
     * Was die Seite über eine Ausleihe wissen muss.
     *
     * @return array<string, mixed>
     */
    private function payload(DeviceLoan $loan): array
    {
        return [
            'id' => $loan->id,
            'borrower' => $loan->borrower_name,
            'outstanding' => $loan->outstanding_quantity,
            'units' => $loan->outstandingUnitNumbers(),
            'unit_labels' => collect($loan->outstandingUnitNumbers())->mapWithKeys(fn ($u) => [$u => $loan->device->unitLabel($u)]),
            'until' => $loan->loan_end_date->format('d.m.Y'),
            'overdue' => $loan->isOverdue(),
            'accessories' => collect($loan->accessories ?? [])
                ->filter(fn ($a) => $a['checked'] ?? true)
                ->map(fn ($a, $i) => ['key' => $i, 'name' => $a['name'], 'quantity' => (int) $a['quantity']])
                ->values(),
        ];
    }
}
