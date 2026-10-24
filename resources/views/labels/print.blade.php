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
        $codeSize = min($h - 4, $w * ($codeType === 'both' ? 0.34 : 0.42));
    @endphp
    <style>
        @page { size: {{ $format['page'] }}; margin: 0; }
        * { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; font-family: ui-sans-serif, system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif; color: #000; background: #e5e5e5; }
        .toolbar { position: sticky; top: 0; display: flex; gap: 12px; align-items: center; padding: 10px 16px; background: #fff; border-bottom: 1px solid #ddd; font-size: 14px; }
        .toolbar button { padding: 8px 14px; border-radius: 8px; border: 0; background: {{ $branding->accent() }}; color: {{ $branding->accentForeground() }}; font-weight: 600; cursor: pointer; }
        .page { width: {{ $isRoll ? $w.'mm' : '210mm' }}; height: {{ $isRoll ? $h.'mm' : '297mm' }}; margin: 12px auto; background: #fff; overflow: hidden;
                padding: {{ $format['margin_top'] }}mm 0 0 {{ $format['margin_left'] }}mm;
                display: grid; grid-template-columns: repeat({{ $format['cols'] }}, {{ $w }}mm); grid-auto-rows: {{ $h }}mm; align-content: start; box-shadow: 0 1px 4px rgba(0,0,0,.15); }
        .label { width: {{ $w }}mm; height: {{ $h }}mm; padding: 1.8mm 2.2mm; display: flex; align-items: center; gap: 2mm; overflow: hidden; }
        .label.empty { visibility: hidden; }
        .qr svg { width: {{ $codeSize }}mm; height: {{ $codeSize }}mm; display: block; }
        .bar svg { height: {{ min(9, $h * 0.3) }}mm; width: 100%; display: block; }
        .text { min-width: 0; flex: 1; display: flex; flex-direction: column; gap: .6mm; }
        .id { font-family: ui-monospace, Menlo, Consolas, monospace; font-weight: 700; font-size: {{ $h < 28 ? '9pt' : '11pt' }}; letter-spacing: .02em; white-space: nowrap; }
        .title { font-size: {{ $h < 28 ? '6.5pt' : '7.5pt' }}; line-height: 1.15; max-height: 2.3em; overflow: hidden; }
        .app { font-size: 5.5pt; color: #555; text-transform: uppercase; letter-spacing: .05em; }
        .note { font-size: {{ $h < 28 ? '5.5pt' : '6.5pt' }}; color: #333; line-height: 1.15; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        @media print {
            html, body { background: #fff; }
            .toolbar { display: none; }
            .page { margin: 0; box-shadow: none; page-break-after: always; }
            .page:last-child { page-break-after: auto; }
        }
    </style>
</head>
<body>
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
                        @if ($appName)<span class="app">{{ $appName }}</span>@endif
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
