@extends('layouts.app')
@section('title', __('Ausleihen'))

@php
    $defaultDays = (int) $settings->get('loans.default_days');
    $i18n = [
        'added' => __(':label hinzugefügt.'),
        'already' => __(':label ist schon ausgewählt.'),
        'busy' => __(':label ist bereits verliehen.'),
        'noneFree' => __('Von „:title“ ist gerade nichts frei.'),
        'maxed' => __('Mehr Exemplare von „:title“ sind nicht frei.'),
        'needUnit' => __('„:title“ wird mit Exemplar-Nummern ausgeliehen – bitte das Etikett des Exemplars scannen oder unten Exemplare wählen.'),
        'notFound' => __('Nicht gefunden.'),
        'error' => __('Fehler beim Nachschlagen.'),
        'remove' => __('Entfernen'),
        'quantity' => __('Menge'),
        'free' => __(':n frei'),
        'unitsHint' => __('Exemplare (optional) – oder Etiketten scannen:'),
        'accessories' => __('Zubehör:'),
        'submit' => __('Ausleihen'),
        'submitOne' => __('1 Exemplar ausleihen'),
        'submitMany' => __(':n Exemplare ausleihen'),
    ];
@endphp

@section('content')
    <x-page-header :title="__('Ausleihen')" :back="route('devices.index', ['ausleihen' => 1])">
        <x-slot:subtitle>{{ __('Etiketten scannen oder IDs eingeben – auch mehrere Geräte. Name und Zeitraum gelten für alle.') }}</x-slot:subtitle>
    </x-page-header>

    <div class="grid gap-6 lg:grid-cols-5" data-checkout
         data-lookup="{{ route('lookup') }}"
         data-devices="{{ $catalog->toJson() }}"
         data-initial="{{ $initial->toJson() }}"
         data-units-required="{{ $unitsRequired ? 1 : 0 }}"
         data-i18n="{{ json_encode($i18n) }}">

        {{-- Erfassen: Kamera, ID, Liste --}}
        <div class="space-y-4 lg:col-span-2">
            <x-scanner aspect="aspect-[4/3]" :hint="__('Kamera starten und die Etiketten nacheinander in den Rahmen halten.')"/>

            <form class="card card-body" data-code-form>
                <label class="label" for="checkout-code">{{ __('Inventar-ID eingeben') }}</label>
                <div class="flex gap-2">
                    <input id="checkout-code" class="input font-mono uppercase" autocomplete="off" autocapitalize="characters" spellcheck="false" placeholder="KAB-0042-3" maxlength="200" autofocus>
                    <button class="btn btn-primary"><x-icon name="plus"/>{{ __('Hinzufügen') }}</button>
                </div>
                <p class="hint" data-checkout-feedback aria-live="polite">{{ __('Hand-Scanner tippen die ID hier ein – Enter fügt hinzu.') }}</p>
            </form>

            <form class="card card-body" data-picker-form>
                <label class="label" for="checkout-picker">{{ __('Oder aus der Liste wählen') }}</label>
                <div class="flex gap-2">
                    <select id="checkout-picker" class="input min-w-0">
                        <option value="">{{ __('Gerät wählen…') }}</option>
                        @foreach ($devices as $device)
                            <option value="{{ $device->id }}">{{ $device->title }} · {{ $device->inventory_number }} ({{ __(':n frei', ['n' => $device->available_quantity]) }})</option>
                        @endforeach
                    </select>
                    <button class="btn btn-secondary"><x-icon name="plus"/><span class="hidden sm:inline">{{ __('Hinzufügen') }}</span></button>
                </div>
            </form>
        </div>

        {{-- Auswahl + gemeinsame Angaben --}}
        <form method="POST" action="{{ route('loans.checkout.store') }}" class="space-y-4 lg:col-span-3" data-checkout-form>
            @csrf
            <section class="card">
                <div class="card-header">
                    <h2 class="card-title">{{ __('Ausgewählt') }}</h2>
                    <span class="badge badge-accent tabular-nums" data-cart-count>0</span>
                </div>
                @if ($errors->has('items') || $errors->has('items.*'))
                    <p class="error border-b border-line px-4 py-3 sm:px-5">{{ $errors->first('items') ?: $errors->first('items.*') }}</p>
                @endif
                <ul class="divide-y divide-line" data-cart-list></ul>
                <div data-cart-empty>
                    <x-empty icon="scan" :title="__('Noch nichts ausgewählt')">{{ __('Etikett scannen, ID eingeben oder ein Gerät aus der Liste wählen.') }}</x-empty>
                </div>
            </section>

            <section class="card">
                <div class="card-header"><h2 class="card-title">{{ __('Für wen und bis wann?') }}</h2></div>
                <div class="card-body space-y-4">
                    <x-field :label="__('Ausleihende Person')" name="borrower_name">
                        <input id="borrower_name" name="borrower_name" class="input" value="{{ old('borrower_name') }}" required maxlength="255" autocomplete="off" placeholder="{{ __('Name, Klasse oder Gruppe') }}">
                    </x-field>
                    <div class="grid grid-cols-2 gap-3">
                        <x-field :label="__('Von')" name="loan_start_date">
                            <input id="loan_start_date" type="date" name="loan_start_date" class="input" value="{{ old('loan_start_date', today()->toDateString()) }}" required>
                        </x-field>
                        <x-field :label="__('Rückgabe bis')" name="loan_end_date">
                            <input id="loan_end_date" type="date" name="loan_end_date" class="input" value="{{ old('loan_end_date', today()->addDays($defaultDays)->toDateString()) }}" required>
                        </x-field>
                    </div>
                    <x-field :label="__('Zweck (optional)')" name="loan_purpose">
                        <input id="loan_purpose" name="loan_purpose" class="input" value="{{ old('loan_purpose') }}" maxlength="255">
                    </x-field>
                    <x-field :label="__('Notiz (optional)')" name="notes">
                        <textarea id="notes" name="notes" rows="2" class="input" maxlength="2000">{{ old('notes') }}</textarea>
                    </x-field>

                    <div data-accessories-confirm hidden>
                        <label class="flex items-start gap-3 rounded-lg border border-line p-3 text-sm has-checked:border-accent has-checked:bg-accent-soft">
                            <input type="hidden" name="accessories_confirmed" value="0">
                            <input type="checkbox" name="accessories_confirmed" value="1" class="checkbox mt-0.5" @checked(old('accessories_confirmed'))>
                            <span>
                                {{ __('Zubehör wie aufgeführt mitgegeben') }}
                                <span class="block text-xs text-muted">{{ __('Fehlt etwas, das Gerät bitte einzeln ausleihen – dort lässt sich Zubehör abhaken.') }}</span>
                            </span>
                        </label>
                        @error('accessories_confirmed')<p class="error">{{ $message }}</p>@enderror
                    </div>
                </div>
                <div class="flex justify-end gap-2 border-t border-line px-4 py-3 sm:px-5">
                    <button class="btn btn-primary" data-checkout-submit disabled><x-icon name="check"/><span data-submit-label>{{ __('Ausleihen') }}</span></button>
                </div>
            </section>
        </form>
    </div>

    <template data-icon="x"><x-icon name="x"/></template>
@endsection
