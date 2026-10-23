<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Device;
use App\Support\Codes;
use App\Support\Settings;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Etiketten mit Inventar-ID, QR-Code und/oder Barcode (Code 128).
 */
class LabelController extends Controller
{
    /** Etiketten pro Druckauftrag (≈ 40 A4-Bögen) */
    public const MAX_LABELS = 1000;

    public function index(Request $request, Settings $settings): View
    {
        $devices = Device::with('category')->orderBy('inventory_number')->get();

        return view('labels.index', [
            'devices' => $devices,
            'categories' => Category::orderBy('name')->get(),
            'formats' => config('loan.label_formats'),
            'preselect' => array_map('intval', (array) $request->query('devices', [])),
            'defaults' => [
                'format' => $settings->get('labels.format'),
                'code_type' => $settings->get('labels.code_type'),
                'show_title' => (bool) $settings->get('labels.show_title'),
            ],
        ]);
    }

    public function print(Request $request, Settings $settings): View
    {
        $data = $request->validate([
            'devices' => ['required', 'array', 'min:1', 'max:500'],
            'devices.*' => ['integer'],
            'mode' => ['required', Rule::in(['device', 'units', 'both'])],
            'format' => ['required', Rule::in(array_keys(config('loan.label_formats')))],
            'code_type' => ['required', Rule::in(['qr', 'barcode', 'both', 'text'])],
            'show_title' => ['nullable', 'boolean'],
            'skip' => ['nullable', 'integer', 'min:0', 'max:100'],
        ]);

        $devices = Device::whereIn('id', $data['devices'])->orderBy('inventory_number')->get();

        // Obergrenze vor dem Erzeugen der Codes (500 Geräte × 9999 Exemplare wären Millionen SVGs)
        $count = $devices->sum(fn (Device $d) => $d->total_quantity <= 1
            ? 1
            : (in_array($data['mode'], ['device', 'both'], true) ? 1 : 0) + (in_array($data['mode'], ['units', 'both'], true) ? $d->total_quantity : 0));
        if ($count > self::MAX_LABELS) {
            throw ValidationException::withMessages(['devices' => __('Höchstens :max Etiketten auf einmal (ausgewählt: :count). Bitte weniger Geräte oder nur Geräte-Etiketten wählen.', [
                'max' => self::MAX_LABELS, 'count' => $count,
            ])]);
        }

        $labels = [];
        foreach ($devices as $device) {
            if (in_array($data['mode'], ['device', 'both'], true) || $device->total_quantity <= 1) {
                $labels[] = ['id' => (string) $device->inventory_number, 'title' => $device->title];
            }
            if (in_array($data['mode'], ['units', 'both'], true) && $device->total_quantity > 1) {
                foreach ($device->unitLabels() as $i => $label) {
                    $labels[] = ['id' => $label, 'title' => $device->title.' ('.($i + 1).'/'.$device->total_quantity.')'];
                }
            }
        }

        $codes = new Codes;
        foreach ($labels as &$label) {
            $url = url('/d/'.rawurlencode($label['id']));
            $label['qr'] = in_array($data['code_type'], ['qr', 'both'], true) ? $codes->qrSvg($url) : null;
            $label['barcode'] = in_array($data['code_type'], ['barcode', 'both'], true) ? $codes->barcodeSvg($label['id']) : null;
        }
        unset($label);

        return view('labels.print', [
            'labels' => $labels,
            'format' => config('loan.label_formats')[$data['format']],
            'codeType' => $data['code_type'],
            'showTitle' => $request->boolean('show_title'),
            'skip' => (int) ($data['skip'] ?? 0),
            'appName' => $settings->get('labels.show_app_name') ? $settings->get('general.app_name') : null,
        ]);
    }

    /**
     * Einzelner QR-Code als SVG (z. B. auf der Geräteseite).
     */
    public function qr(Device $device, ?int $unit = null): Response
    {
        abort_if($unit !== null && ($unit < 1 || $unit > $device->total_quantity), 404);

        $id = $unit ? $device->unitLabel($unit) : (string) $device->inventory_number;

        return response((new Codes)->qrSvg(url('/d/'.rawurlencode($id)), 240), 200, [
            'Content-Type' => 'image/svg+xml',
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }
}
