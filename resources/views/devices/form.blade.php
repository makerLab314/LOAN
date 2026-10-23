@extends('layouts.app')
@section('title', $device->exists ? __('Gerät bearbeiten') : __('Neues Gerät'))

@php
    $accessoryRows = old('accessories', $device->exists
        ? $device->accessories->map(fn ($a) => ['name' => $a->name, 'quantity' => $a->quantity])->all()
        : []);
    $accessoryRows = array_values(array_filter((array) $accessoryRows, 'is_array'));
@endphp

@section('content')
    <x-page-header :title="$device->exists ? __('Gerät bearbeiten') : __('Neues Gerät')"
                   :back="$device->exists ? route('devices.show', $device) : route('devices.index')"/>

    <form method="POST" enctype="multipart/form-data"
          action="{{ $device->exists ? route('devices.update', $device) : route('devices.store') }}"
          class="grid gap-6 lg:grid-cols-3">
        @csrf
        @if ($device->exists) @method('PUT') @endif

        <div class="space-y-6 lg:col-span-2">
            <section class="card">
                <div class="card-header"><h2 class="card-title">{{ __('Stammdaten') }}</h2></div>
                <div class="card-body space-y-4">
                    <x-field :label="__('Bezeichnung')" name="title">
                        <input id="title" name="title" class="input @error('title') input-error @enderror" value="{{ old('title', $device->title) }}" required maxlength="255" autofocus>
                    </x-field>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <x-field :label="__('Kategorie')" name="category_id">
                            <select id="category_id" name="category_id" class="input" required>
                                <option value="">{{ __('Bitte wählen…') }}</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}" @selected(old('category_id', $device->category_id) == $category->id)>
                                        {{ $category->name }} ({{ $category->code }})
                                    </option>
                                @endforeach
                            </select>
                        </x-field>
                        <x-field :label="__('Anzahl Exemplare')" name="total_quantity" :hint="__('Bei mehreren Exemplaren erhalten die Etiketten die Endung -1, -2 …')">
                            <input id="total_quantity" type="number" name="total_quantity" min="1" max="9999" class="input" value="{{ old('total_quantity', $device->total_quantity ?? 1) }}" required>
                        </x-field>
                    </div>

                    <x-field :label="__('Inventar-ID')" name="inventory_number"
                             :hint="$device->exists ? __('Achtung: Änderungen machen bereits gedruckte Etiketten ungültig.') : __('Leer lassen für automatische Vergabe (Kategorie-Kürzel + Nummer).')">
                        <input id="inventory_number" name="inventory_number" class="input font-mono uppercase" value="{{ old('inventory_number', $device->inventory_number) }}"
                               maxlength="32" pattern="[A-Za-z0-9][A-Za-z0-9._\-]*" placeholder="{{ __('automatisch') }}" autocomplete="off">
                    </x-field>

                    <x-field :label="__('Beschreibung')" name="description">
                        <textarea id="description" name="description" rows="4" class="input" maxlength="5000">{{ old('description', $device->description) }}</textarea>
                    </x-field>
                </div>
            </section>

            <section class="card" x-data="accessoryEditor" data-rows='@json($accessoryRows)'>
                <div class="card-header">
                    <div>
                        <h2 class="card-title">{{ __('Zubehör-Checkliste') }}</h2>
                        <p class="text-xs text-muted">{{ __('Wird bei Ausgabe und Rückgabe abgefragt – Mengen je Exemplar.') }}</p>
                    </div>
                    <button type="button" class="btn btn-secondary btn-sm" @click="add"><x-icon name="plus"/>{{ __('Zubehör') }}</button>
                </div>
                <div class="card-body space-y-2">
                    <template x-for="(row, index) in rows" :key="index">
                        <div class="flex items-center gap-2">
                            <input type="text" class="input flex-1" data-accessory-name :name="nameField(index)" x-model="row.name" maxlength="255" placeholder="{{ __('z. B. Kaltgerätekabel') }}">
                            <input type="number" class="input w-20" :name="quantityField(index)" x-model="row.quantity" min="1" max="999" aria-label="{{ __('Menge') }}">
                            <button type="button" class="btn btn-ghost btn-icon" @click="remove(index)" aria-label="{{ __('Entfernen') }}"><x-icon name="x"/></button>
                        </div>
                    </template>
                    <p x-show="rows.length === 0" class="text-sm text-muted">{{ __('Kein Zubehör hinterlegt.') }}</p>
                </div>
            </section>
        </div>

        <div class="space-y-6">
            <section class="card">
                <div class="card-header"><h2 class="card-title">{{ __('Bild') }}</h2></div>
                <div class="card-body space-y-3">
                    @if ($device->image_url)
                        <img src="{{ $device->thumb_url }}" alt="" class="aspect-square w-full rounded-lg bg-surface-2 object-cover">
                        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="remove_image" value="1" class="checkbox"> {{ __('Bild entfernen') }}</label>
                    @endif
                    <x-field name="image" :hint="__('JPG, PNG oder WebP, max. 10 MB. Wird verkleinert und ohne Metadaten gespeichert.')">
                        <input type="file" name="image" accept="image/jpeg,image/png,image/webp" capture="environment"
                               class="block w-full text-sm text-muted file:mr-3 file:rounded-lg file:border-0 file:bg-surface-2 file:px-3 file:py-2 file:text-sm file:font-medium file:text-fg hover:file:bg-line">
                    </x-field>
                </div>
            </section>

            <div class="flex gap-2">
                <a href="{{ $device->exists ? route('devices.show', $device) : route('devices.index') }}" class="btn btn-secondary flex-1">{{ __('Abbrechen') }}</a>
                <button class="btn btn-primary flex-1"><x-icon name="check"/>{{ __('Speichern') }}</button>
            </div>
        </div>
    </form>
@endsection
