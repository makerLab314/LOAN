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

        <section class="card h-fit">
            <div class="card-header"><h2 class="card-title">{{ __('Optionen') }}</h2></div>
            <div class="card-body space-y-4">
                <x-field :label="__('Etiketten pro Gerät')" name="mode">
                    <select name="mode" class="input">
                        <option value="units">{{ __('Je Exemplar (-1, -2 …)') }}</option>
                        <option value="device">{{ __('Ein Etikett pro Gerät') }}</option>
                        <option value="both">{{ __('Beides') }}</option>
                    </select>
                </x-field>
                <x-field :label="__('Format')" name="format">
                    <select name="format" class="input">
                        @foreach ($formats as $key => $format)
                            <option value="{{ $key }}" @selected($defaults['format'] === $key)>{{ $format['label'] }}</option>
                        @endforeach
                    </select>
                </x-field>
                <x-field :label="__('Code')" name="code_type">
                    <select name="code_type" class="input">
                        <option value="qr" @selected($defaults['code_type'] === 'qr')>{{ __('QR-Code') }}</option>
                        <option value="barcode" @selected($defaults['code_type'] === 'barcode')>{{ __('Barcode (Code 128)') }}</option>
                        <option value="both" @selected($defaults['code_type'] === 'both')>{{ __('QR + Barcode') }}</option>
                        <option value="text" @selected($defaults['code_type'] === 'text')>{{ __('Nur Text') }}</option>
                    </select>
                </x-field>
                <x-field :label="__('Leere Etiketten überspringen')" name="skip" :hint="__('Für angebrochene Bögen.')">
                    <input type="number" name="skip" class="input w-24" min="0" max="100" value="0">
                </x-field>
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" name="show_title" value="1" class="checkbox" @checked($defaults['show_title'])> {{ __('Gerätename aufdrucken') }}
                </label>
                <button class="btn btn-primary w-full"><x-icon name="printer"/>{{ __('Druckansicht öffnen') }}</button>
            </div>
        </section>
    </form>
@endsection
