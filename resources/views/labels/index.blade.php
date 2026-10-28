@extends('layouts.app')
@section('title', __('Etiketten'))

@section('content')
    <x-page-header :title="__('Etiketten drucken')">
        <x-slot:subtitle>{{ __('QR-Codes öffnen das Gerät direkt mit der Handy-Kamera. Für Etikettendrucker ohne QR gibt es Barcodes (Code 128) oder nur Text.') }}</x-slot:subtitle>
    </x-page-header>

    <form method="GET" action="{{ route('labels.print') }}" target="_blank" class="grid gap-6 lg:grid-cols-3">
        <section class="card lg:col-span-2" data-label-picker>
            <div class="card-header flex-wrap">
                <h2 class="card-title">{{ __('Geräte') }} · <span data-selected-count>0</span> {{ __('ausgewählt') }}</h2>
                <div class="flex gap-2">
                    <button type="button" class="btn btn-secondary btn-sm" data-select-visible>{{ __('Alle sichtbaren') }}</button>
                    <button type="button" class="btn btn-ghost btn-sm" data-select-none>{{ __('Keine') }}</button>
                </div>
            </div>
            <div class="flex flex-col gap-2 border-b border-line p-3 sm:flex-row">
                <input type="search" class="input flex-1" placeholder="{{ __('Filtern…') }}" data-label-search>
                <select class="input sm:w-52" data-label-category>
                    <option value="">{{ __('Alle Kategorien') }}</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <ul class="max-h-[60vh] divide-y divide-line overflow-y-auto">
                @foreach ($devices as $device)
                    <li data-item data-category="{{ $device->category_id }}" data-search="{{ mb_strtolower($device->title.' '.$device->inventory_number) }}">
                        <label class="flex cursor-pointer items-center gap-3 px-4 py-2.5 hover:bg-surface-2/60">
                            <input type="checkbox" name="devices[]" value="{{ $device->id }}" class="checkbox" @checked(in_array($device->id, $preselect, true))>
                            <span class="id-chip">{{ $device->inventory_number }}</span>
                            <span class="min-w-0 flex-1 truncate text-sm">{{ $device->title }}</span>
                            @if ($device->total_quantity > 1)<span class="badge badge-muted">{{ $device->total_quantity }}×</span>@endif
                        </label>
                    </li>
                @endforeach
            </ul>
            @error('devices')<p class="error px-4 pb-3">{{ $message }}</p>@enderror
        </section>

        <section class="card h-fit" data-label-options>
            <div class="card-header">
                <h2 class="card-title">{{ __('Optionen') }}</h2>
                <button type="button" class="btn btn-ghost btn-sm" data-label-reset>{{ __('Zurücksetzen') }}</button>
            </div>
            <div class="card-body space-y-4">
                <x-field :label="__('Etiketten pro Gerät')" name="mode">
                    <select id="mode" name="mode" class="input">
                        <option value="units">{{ __('Je Exemplar (-1, -2 …)') }}</option>
                        <option value="device">{{ __('Ein Etikett pro Gerät') }}</option>
                        <option value="both">{{ __('Beides') }}</option>
                    </select>
                </x-field>
                <x-field :label="__('Format')" name="format">
                    <select id="format" name="format" class="input" data-label-format>
                        @foreach (collect($formats)->groupBy('group', true) as $group => $items)
                            <optgroup label="{{ __($group) }}">
                                @foreach ($items as $key => $format)
                                    <option value="{{ $key }}" @selected($defaults['format'] === $key)>{{ $format['label'] }}</option>
                                @endforeach
                            </optgroup>
                        @endforeach
                        <option value="custom" @selected(old('format') === 'custom')>{{ __('Eigenes Format …') }}</option>
                    </select>
                </x-field>

                {{-- Eigenes Format: Maße in mm --}}
                <fieldset class="space-y-3 rounded-xl border border-line p-3" data-label-custom hidden>
                    <legend class="px-1 text-xs font-semibold tracking-wider text-muted uppercase">{{ __('Eigenes Format (mm)') }}</legend>
                    <div class="grid grid-cols-2 gap-3">
                        <x-field :label="__('Breite')" name="custom_w"><input id="custom_w" type="number" name="custom_w" class="input" min="10" max="200" step="0.1" value="{{ old('custom_w', 50) }}"></x-field>
                        <x-field :label="__('Höhe')" name="custom_h"><input id="custom_h" type="number" name="custom_h" class="input" min="5" max="280" step="0.1" value="{{ old('custom_h', 20) }}"></x-field>
                    </div>
                    <label class="flex items-center gap-2 text-sm">
                        <input type="checkbox" name="custom_sheet" value="1" class="checkbox" data-label-custom-sheet @checked(old('custom_sheet'))> {{ __('Mehrere auf einem A4-Bogen') }}
                    </label>
                    <div class="grid grid-cols-2 gap-3" data-label-custom-grid hidden>
                        <x-field :label="__('Spalten')" name="custom_cols"><input id="custom_cols" type="number" name="custom_cols" class="input" min="1" max="10" value="{{ old('custom_cols', 3) }}"></x-field>
                        <x-field :label="__('Zeilen')" name="custom_rows"><input id="custom_rows" type="number" name="custom_rows" class="input" min="1" max="40" value="{{ old('custom_rows', 10) }}"></x-field>
                        <x-field :label="__('Rand oben')" name="custom_margin_top"><input id="custom_margin_top" type="number" name="custom_margin_top" class="input" min="0" max="60" step="0.05" value="{{ old('custom_margin_top', 0) }}"></x-field>
                        <x-field :label="__('Rand links')" name="custom_margin_left"><input id="custom_margin_left" type="number" name="custom_margin_left" class="input" min="0" max="60" step="0.05" value="{{ old('custom_margin_left', 0) }}"></x-field>
                        <x-field :label="__('Abstand waagrecht')" name="custom_gap_x"><input id="custom_gap_x" type="number" name="custom_gap_x" class="input" min="0" max="20" step="0.05" value="{{ old('custom_gap_x', 0) }}"></x-field>
                        <x-field :label="__('Abstand senkrecht')" name="custom_gap_y"><input id="custom_gap_y" type="number" name="custom_gap_y" class="input" min="0" max="20" step="0.05" value="{{ old('custom_gap_y', 0) }}"></x-field>
                    </div>
                    <p class="hint">{{ __('Die Maße stehen meist auf der Verpackung der Etiketten.') }}</p>
                </fieldset>
                <x-field :label="__('Code')" name="code_type">
                    <select name="code_type" class="input">
                        <option value="qr" @selected($defaults['code_type'] === 'qr')>{{ __('QR-Code') }}</option>
                        <option value="barcode" @selected($defaults['code_type'] === 'barcode')>{{ __('Barcode (Code 128)') }}</option>
                        <option value="both" @selected($defaults['code_type'] === 'both')>{{ __('QR + Barcode') }}</option>
                        <option value="text" @selected($defaults['code_type'] === 'text')>{{ __('Nur Text') }}</option>
                    </select>
                </x-field>
                <div class="grid grid-cols-2 gap-3">
                    <x-field :label="__('Kopien je Etikett')" name="copies" :hint="__('z. B. für Gerät und Netzteil.')">
                        <input type="number" name="copies" class="input w-24" min="1" max="10" value="1">
                    </x-field>
                    <x-field :label="__('Leere Etiketten überspringen')" name="skip" :hint="__('Für angebrochene Bögen.')">
                        <input type="number" name="skip" class="input w-24" min="0" max="100" value="0">
                    </x-field>
                </div>
                <x-field :label="__('Zusatzzeile')" name="note" :hint="__('Optional, z. B. „Eigentum des MakerLab“.')">
                    <input name="note" class="input" maxlength="40" value="{{ $defaults['note'] }}">
                </x-field>
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" name="show_title" value="1" class="checkbox" @checked($defaults['show_title'])> {{ __('Gerätename aufdrucken') }}
                </label>

                {{-- Gestaltung --}}
                <div class="space-y-4 border-t border-line pt-4">
                    <p class="text-xs font-semibold tracking-wider text-muted uppercase">{{ __('Gestaltung') }}</p>
                    <div>
                        <span class="label">{{ __('Anordnung') }}</span>
                        <div class="segmented" role="radiogroup" aria-label="{{ __('Anordnung') }}">
                            <label><input type="radio" name="layout" value="row" checked><span>{{ __('Code links') }}</span></label>
                            <label><input type="radio" name="layout" value="row-reverse"><span>{{ __('Code rechts') }}</span></label>
                            <label><input type="radio" name="layout" value="column"><span>{{ __('Code oben') }}</span></label>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <span class="label">{{ __('Schrift') }}</span>
                            <div class="segmented" role="radiogroup" aria-label="{{ __('Schriftgröße') }}">
                                <label><input type="radio" name="text_size" value="s"><span>S</span></label>
                                <label><input type="radio" name="text_size" value="m" checked><span>M</span></label>
                                <label><input type="radio" name="text_size" value="l"><span>L</span></label>
                            </div>
                        </div>
                        <div>
                            <span class="label">{{ __('Code-Größe') }}</span>
                            <div class="segmented" role="radiogroup" aria-label="{{ __('Code-Größe') }}">
                                <label><input type="radio" name="code_size" value="s"><span>S</span></label>
                                <label><input type="radio" name="code_size" value="m" checked><span>M</span></label>
                                <label><input type="radio" name="code_size" value="l"><span>L</span></label>
                            </div>
                        </div>
                    </div>
                    <div>
                        <span class="label">{{ __('Text') }}</span>
                        <div class="segmented" role="radiogroup" aria-label="{{ __('Textausrichtung') }}">
                            <label><input type="radio" name="align" value="left" checked><span>{{ __('Linksbündig') }}</span></label>
                            <label><input type="radio" name="align" value="center"><span>{{ __('Zentriert') }}</span></label>
                        </div>
                    </div>
                    <label class="flex items-start gap-2 text-sm">
                        <input type="checkbox" name="cut_lines" value="1" class="checkbox mt-0.5">
                        <span>{{ __('Schnittlinien') }}<span class="hint block">{{ __('Zum Ausschneiden, z. B. für Kabelanhänger auf Folie.') }}</span></span>
                    </label>
                    @if ($branding->logoUrl())
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" name="show_logo" value="1" class="checkbox"> {{ __('Logo aufdrucken') }}
                        </label>
                    @endif
                    <details class="group rounded-xl border border-line">
                        <summary class="flex cursor-pointer items-center justify-between px-3 py-2 text-sm font-medium select-none">
                            {{ __('Druckversatz korrigieren') }}
                            <x-icon name="chevron-down" class="size-4 text-muted transition group-open:rotate-180"/>
                        </summary>
                        <div class="grid grid-cols-2 gap-3 border-t border-line p-3">
                            <x-field :label="__('Nach rechts (mm)')" name="offset_x"><input id="offset_x" type="number" name="offset_x" class="input" min="-10" max="10" step="0.5" value="0"></x-field>
                            <x-field :label="__('Nach unten (mm)')" name="offset_y"><input id="offset_y" type="number" name="offset_y" class="input" min="-10" max="10" step="0.5" value="0"></x-field>
                            <p class="hint col-span-2">{{ __('Falls der Drucker alles leicht verschoben druckt. Negative Werte verschieben nach links bzw. oben.') }}</p>
                        </div>
                    </details>
                </div>

                <button class="btn btn-primary w-full"><x-icon name="printer"/>{{ __('Druckansicht öffnen') }}</button>
                <p class="hint text-center">{{ __('Die Einstellungen merkt sich dieser Browser.') }}</p>
            </div>
        </section>
    </form>
@endsection
