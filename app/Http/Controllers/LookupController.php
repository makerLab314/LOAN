<?php

namespace App\Http\Controllers;

use App\Services\InventoryNumbers;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Inventar-ID eingeben oder scannen → Gerät öffnen.
 * Die QR-Codes auf den Etiketten zeigen auf /d/{id}, funktionieren also
 * auch mit der normalen Handy-Kamera.
 */
class LookupController extends Controller
{
    public function __construct(private InventoryNumbers $numbers) {}

    public function scan(): View
    {
        return view('scan');
    }

    public function show(string $code): RedirectResponse
    {
        $hit = $this->numbers->resolve($code);

        if (! $hit) {
            return redirect()->route('scan')->with('error', __('Keine Inventar-ID „:code“ gefunden.', ['code' => mb_substr($code, 0, 40)]));
        }

        return redirect()->route('devices.show', array_filter([
            'device' => $hit['device']->id,
            'unit' => $hit['unit'],
        ]));
    }

    public function lookup(Request $request): JsonResponse|RedirectResponse
    {
        $code = (string) $request->validate(['code' => ['required', 'string', 'max:200']])['code'];
        $hit = $this->numbers->resolve($code);

        if ($request->expectsJson()) {
            return $hit
                ? response()->json([
                    'found' => true,
                    'title' => $hit['device']->title,
                    'label' => $hit['unit'] ? $hit['device']->unitLabel($hit['unit']) : $hit['device']->inventory_number,
                    'url' => route('devices.show', array_filter(['device' => $hit['device']->id, 'unit' => $hit['unit']])),
                ])
                : response()->json(['found' => false, 'message' => __('Keine Inventar-ID „:code“ gefunden.', ['code' => mb_substr($code, 0, 40)])], 404);
        }

        return $this->show($code);
    }
}
