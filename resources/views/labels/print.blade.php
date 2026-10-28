<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Etiketten') }} · {{ $branding->name() }}</title>
    @php
        $w = $format['w']; $h = $format['h'];
        $perPage = $format['cols'] * $format['rows'];
        $isRoll = $perPage === 1;
        $slots = array_merge(array_fill(0, $isRoll ? 0 : $skip, null), $labels);
        $pages = $isRoll ? array_map(fn ($l) => [$l], $slots) : array_chunk($slots, $perPage);

        // Kleine Etiketten (Kabel, Schriftband) bekommen weniger Rand und kleinere Schrift
        $tiny = $h < 20;
        $padY = $tiny ? 0.8 : 1.8;
        $padX = $tiny ? 1.2 : 2.2;
        $column = $style['layout'] === 'column';
        $codeSize = $column
            ? min($w - 2 * $padX, ($h - 2 * $padY) * ($codeType === 'both' ? 0.34 : 0.55)) * $style['code']
            : min($h - 2 * $padY, $w * ($codeType === 'both' ? 0.34 : 0.42) * $style['code']);
        $codeSize = round(max(6, min($codeSize, $h - 2 * $padY, $w - 2 * $padX)), 2);
        $pt = fn (float $size) => round($size * $style['text'], 2).'pt';
        $idSize = $tiny ? 7.5 : ($h < 28 ? 9 : 11);
    @endphp
    <style>
        @page { size: {{ $format['page'] }}; margin: 0; }
        * { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; font-family: ui-sans-serif, system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif; color: #000; background: #e5e5e5; }
        .toolbar { position: sticky; top: 0; z-index: 1; display: flex; flex-wrap: wrap; gap: 12px; align-items: center; padding: 10px 16px; background: #fff; border-bottom: 1px solid #ddd; font-size: 14px; }
        .toolbar button { padding: 8px 14px; border-radius: 8px; border: 0; background: {{ $branding->accent() }}; color: {{ $branding->accentForeground() }}; font-weight: 600; cursor: pointer; }
        .page { width: {{ $isRoll ? $w.'mm' : '210mm' }}; height: {{ $isRoll ? $h.'mm' : '297mm' }}; margin: 12px auto; background: #fff; overflow: hidden;
                padding: {{ $format['margin_top'] + $style['offset_y'] }}mm 0 0 {{ $format['margin_left'] + $style['offset_x'] }}mm;
                display: grid; grid-template-columns: repeat({{ $format['cols'] }}, {{ $w }}mm); grid-auto-rows: {{ $h }}mm;
                column-gap: {{ $format['gap_x'] }}mm; row-gap: {{ $format['gap_y'] }}mm; align-content: start; box-shadow: 0 1px 4px rgba(0,0,0,.15); }
        .label { width: {{ $w }}mm; height: {{ $h }}mm; padding: {{ $padY }}mm {{ $padX }}mm; display: flex; align-items: center; gap: {{ $tiny ? 1.2 : 2 }}mm; overflow: hidden;
                 flex-direction: {{ $column ? 'column' : $style['layout'] }}; {{ $column ? 'justify-content: center;' : '' }} }
        .label.empty { visibility: hidden; }
        .cut .label:not(.empty) { outline: 0.2mm dashed #999; outline-offset: -0.1mm; }
        .qr svg { width: {{ $codeSize }}mm; height: {{ $codeSize }}mm; display: block; }
        .bar svg { height: {{ round(min(9, $h * ($column ? 0.15 : 0.3)), 2) }}mm; width: 100%; display: block; }
        .text { min-width: 0; {{ $column ? 'width: 100%;' : 'flex: 1;' }} display: flex; flex-direction: column; gap: {{ $tiny ? 0.3 : 0.6 }}mm;
                text-align: {{ $column ? 'center' : $style['align'] }}; align-items: {{ $column || $style['align'] === 'center' ? 'center' : 'stretch' }}; }
        .brand { display: flex; align-items: center; gap: 1mm; }
        .brand img { height: {{ $tiny ? 2.5 : 3.5 }}mm; width: auto; }
        .id { font-family: ui-monospace, Menlo, Consolas, monospace; font-weight: 700; font-size: {{ $pt($idSize) }}; letter-spacing: .02em; white-space: nowrap; }
        .title { font-size: {{ $pt($tiny ? 5.5 : ($h < 28 ? 6.5 : 7.5)) }}; line-height: 1.15; max-height: 2.3em; overflow: hidden; }
        .app { font-size: {{ $pt($tiny ? 4.5 : 5.5) }}; color: #555; text-transform: uppercase; letter-spacing: .05em; }
        .note { font-size: {{ $pt($tiny ? 4.5 : ($h < 28 ? 5.5 : 6.5)) }}; color: #333; line-height: 1.15; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 100%; }
        .bar { width: 100%; }
        @media print {
            html, body { background: #fff; }
            .toolbar { display: none; }
            .page { margin: 0; box-shadow: none; page-break-after: always; }
            .page:last-child { page-break-after: auto; }
        }
    </style>
</head>
<body @class(['cut' => $style['cut_lines']])>
<div class="toolbar">
    <button type="button" id="print">{{ __('Drucken') }}</button>
    <span>{{ trans_choice(':count Etikett|:count Etiketten', count($labels)) }} · {{ $format['label'] }} · {{ __('Druckskalierung auf 100 % stellen.') }}</span>
</div>
@foreach ($pages as $page)
    <div class="page">
        @foreach ($page as $label)
            @if ($label === null)
                <div class="label empty"></div>
            @else
                <div class="label">
                    @if ($label['qr'])<div class="qr">{!! $label['qr'] !!}</div>@endif
                    <div class="text">
                        @if ($logo || $appName)
                            <span class="brand">
                                @if ($logo)<img src="{{ $logo }}" alt="">@endif
                                @if ($appName)<span class="app">{{ $appName }}</span>@endif
                            </span>
                        @endif
                        <span class="id">{{ $label['id'] }}</span>
                        @if ($showTitle)<span class="title">{{ $label['title'] }}</span>@endif
                        @if ($note !== '')<span class="note">{{ $note }}</span>@endif
                        @if ($label['barcode'])<div class="bar">{!! $label['barcode'] !!}</div>@endif
                    </div>
                </div>
            @endif
        @endforeach
    </div>
@endforeach
<script nonce="{{ Vite::cspNonce() }}">document.getElementById('print').addEventListener('click', function () { window.print(); });</script>
</body>
</html>
