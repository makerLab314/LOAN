<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Device;
use App\Support\Branding;
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
                'note' => (string) $settings->get('labels.note'),
            ],
        ]);
    }

    public function print(Request $request, Settings $settings, Branding $branding): View
    {
        $data = $request->validate([
            'devices' => ['required', 'array', 'min:1', 'max:500'],
            'devices.*' => ['integer'],
            'mode' => ['required', Rule::in(['device', 'units', 'both'])],
            'format' => ['required', Rule::in([...array_keys(config('loan.label_formats')), 'custom'])],
            'code_type' => ['required', Rule::in(['qr', 'barcode', 'both', 'text'])],
            'show_title' => ['nullable', 'boolean'],
            'skip' => ['nullable', 'integer', 'min:0', 'max:100'],
            'copies' => ['nullable', 'integer', 'min:1', 'max:10'],
            'note' => ['nullable', 'string', 'max:40'],
            // Gestaltung
            'layout' => ['nullable', Rule::in(['row', 'row-reverse', 'column'])],
            'text_size' => ['nullable', Rule::in(['s', 'm', 'l'])],
            'code_size' => ['nullable', Rule::in(['s', 'm', 'l'])],
            'align' => ['nullable', Rule::in(['left', 'center'])],
            'cut_lines' => ['nullable', 'boolean'],
            'show_logo' => ['nullable', 'boolean'],
            'offset_x' => ['nullable', 'numeric', 'between:-10,10'],
            'offset_y' => ['nullable', 'numeric', 'between:-10,10'],
            // Eigenes Format
            'custom_sheet' => ['nullable', 'boolean'],
            'custom_w' => ['exclude_unless:format,custom', 'required', 'numeric', 'between:10,200'],
            'custom_h' => ['exclude_unless:format,custom', 'required', 'numeric', 'between:5,280'],
            'custom_cols' => ['exclude_unless:format,custom', 'nullable', 'integer', 'between:1,10'],
            'custom_rows' => ['exclude_unless:format,custom', 'nullable', 'integer', 'between:1,40'],
            'custom_margin_top' => ['exclude_unless:format,custom', 'nullable', 'numeric', 'between:0,60'],
            'custom_margin_left' => ['exclude_unless:format,custom', 'nullable', 'numeric', 'between:0,60'],
            'custom_gap_x' => ['exclude_unless:format,custom', 'nullable', 'numeric', 'between:0,20'],
            'custom_gap_y' => ['exclude_unless:format,custom', 'nullable', 'numeric', 'between:0,20'],
        ]);
        $copies = (int) ($data['copies'] ?? 1);
        $format = $data['format'] === 'custom' ? $this->customFormat($request, $data) : config('loan.label_formats')[$data['format']];

        $devices = Device::whereIn('id', $data['devices'])->orderBy('inventory_number')->get();

        // Obergrenze vor dem Erzeugen der Codes (500 Geräte × 9999 Exemplare wären Millionen SVGs)
        $count = $devices->sum(fn (Device $d) => $d->total_quantity <= 1
            ? 1
            : (in_array($data['mode'], ['device', 'both'], true) ? 1 : 0) + (in_array($data['mode'], ['units', 'both'], true) ? $d->total_quantity : 0)) * $copies;
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

        // Kopien direkt hintereinander (z. B. Gerät + Netzteil), Codes nur einmal erzeugt
        if ($copies > 1) {
            $labels = array_merge(...array_map(fn (array $l) => array_fill(0, $copies, $l), $labels));
        }

        return view('labels.print', [
            'labels' => $labels,
            'format' => $format + ['gap_x' => 0, 'gap_y' => 0],
            'codeType' => $data['code_type'],
            'showTitle' => $request->boolean('show_title'),
            'skip' => (int) ($data['skip'] ?? 0),
            'note' => trim((string) ($data['note'] ?? '')),
            'appName' => $settings->get('labels.show_app_name') ? $settings->get('general.app_name') : null,
            'logo' => $request->boolean('show_logo') ? $branding->logoUrl() : null,
            'style' => [
                'layout' => $data['layout'] ?? 'row',
                'text' => ['s' => 0.85, 'm' => 1.0, 'l' => 1.2][$data['text_size'] ?? 'm'],
                'code' => ['s' => 0.8, 'm' => 1.0, 'l' => 1.2][$data['code_size'] ?? 'm'],
                'align' => $data['align'] ?? 'left',
                'cut_lines' => $request->boolean('cut_lines'),
                'offset_x' => (float) ($data['offset_x'] ?? 0),
                'offset_y' => (float) ($data['offset_y'] ?? 0),
            ],
        ]);
    }

    /**
     * Eigenes Format aus dem Formular. Als Bogen (A4) muss es auf die Seite passen.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function customFormat(Request $request, array $data): array
    {
        $w = (float) $data['custom_w'];
        $h = (float) $data['custom_h'];

        if (! $request->boolean('custom_sheet')) {
            return ['label' => __('Eigenes Format :w × :h mm (Rolle)', ['w' => $w, 'h' => $h]), 'page' => "{$w}mm {$h}mm",
                'cols' => 1, 'rows' => 1, 'w' => $w, 'h' => $h, 'margin_top' => 0, 'margin_left' => 0];
        }

        $format = [
            'label' => __('Eigenes Format :w × :h mm (A4)', ['w' => $w, 'h' => $h]), 'page' => 'A4', 'w' => $w, 'h' => $h,
            'cols' => (int) ($data['custom_cols'] ?? 1), 'rows' => (int) ($data['custom_rows'] ?? 1),
            'margin_top' => (float) ($data['custom_margin_top'] ?? 0), 'margin_left' => (float) ($data['custom_margin_left'] ?? 0),
            'gap_x' => (float) ($data['custom_gap_x'] ?? 0), 'gap_y' => (float) ($data['custom_gap_y'] ?? 0),
        ];
        $width = $format['margin_left'] + $format['cols'] * $w + ($format['cols'] - 1) * $format['gap_x'];
        $height = $format['margin_top'] + $format['rows'] * $h + ($format['rows'] - 1) * $format['gap_y'];
        if ($width > 210.5 || $height > 297.5) {
            throw ValidationException::withMessages(['custom_w' => __('Das Format passt nicht auf A4 (:w × :h mm belegt).', [
                'w' => round($width, 1), 'h' => round($height, 1),
            ])]);
        }

        return $format;
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
