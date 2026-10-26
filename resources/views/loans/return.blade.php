@extends('layouts.app')
@section('title', __('Zurückgeben'))

@php
    $kiosk = auth()->user()->isKiosk();
    $i18n = [
        'added' => __(':label erfasst.'),
        'already' => __(':label ist schon erfasst.'),
        'notLoaned' => __(':label ist gerade nicht verliehen.'),
        'scanUnit' => __('„:title“ wurde mit Exemplar-Nummern verliehen – bitte das Etikett des Exemplars scannen.'),
        'choose' => __('„:title“ ist mehrfach verliehen – bitte unten auswählen, wer zurückgibt.'),
        'chooseTitle' => __('Wer gibt „:title“ zurück?'),
        'notFound' => __('Nicht gefunden.'),
        'error' => __('Fehler beim Nachschlagen.'),
        'remove' => __('Entfernen'),
        'until' => __('bis :date'),
        'overdue' => __('überfällig'),
        'of' => __('von :n'),
        'scanned' => __(':n von :m Exemplaren gescannt'),
        'missingUnits' => __('Nicht gescannte Exemplare bleiben verliehen.'),
        'accessories' => __('Zubehör vollständig zurück?'),
        'accessoriesHint' => __('Nicht angehaktes Zubehör wird als fehlend vermerkt.'),
        'submit' => __('Rückgabe buchen'),
        'submitMany' => __(':n Rückgaben buchen'),
    ];
@endphp

@section('content')
    <x-page-header :title="__('Zurückgeben')" :back="route('loans.index')">
        <x-slot:subtitle>{{ $kiosk
            ? __('Bitte jedes Gerät scannen oder seine Inventar-ID eingeben – zurückgebucht wird nur, was erfasst wurde.')
            : __('Etiketten scannen oder IDs eingeben – auch mehrere Geräte auf einmal.') }}</x-slot:subtitle>
    </x-page-header>

    <div class="grid gap-6 lg:grid-cols-5" data-return
         data-lookup="{{ route('loans.return-scan.lookup') }}"
         data-initial="{{ $initial->toJson() }}"
         data-i18n="{{ json_encode($i18n) }}">

        <div class="space-y-4 lg:col-span-2">
            <x-scanner aspect="aspect-[4/3]" :hint="__('Kamera starten und die Etiketten nacheinander in den Rahmen halten.')"/>

            <form class="card card-body" data-code-form>
                <label class="label" for="return-code">{{ __('Inventar-ID eingeben') }}</label>
                <div class="flex gap-2">
                    <input id="return-code" class="input font-mono uppercase" autocomplete="off" autocapitalize="characters" spellcheck="false" placeholder="KAB-0042-3" maxlength="200" autofocus>
                    <button class="btn btn-primary"><x-icon name="plus"/>{{ __('Erfassen') }}</button>
                </div>
                <p class="hint" data-return-feedback aria-live="polite">{{ __('Die ID steht auf dem Etikett am Gerät. Hand-Scanner tippen sie hier ein.') }}</p>
            </form>
        </div>

        <form method="POST" action="{{ route('loans.return-scan.store') }}" class="space-y-4 lg:col-span-3" data-return-form>
            @csrf
            <section class="card">
                <div class="card-header">
                    <h2 class="card-title">{{ __('Erfasst') }}</h2>
                    <span class="badge badge-accent tabular-nums" data-cart-count>0</span>
                </div>
                @if ($errors->any())
                    <p class="error border-b border-line px-4 py-3 sm:px-5">{{ $errors->first('returns') ?: $errors->first() }}</p>
                @endif
                <div data-choice></div>
                <ul class="divide-y divide-line" data-cart-list></ul>
                <div data-cart-empty>
                    <x-empty icon="undo" :title="__('Noch nichts erfasst')">{{ __('Etikett scannen oder Inventar-ID eingeben – die passende Ausleihe erscheint hier.') }}</x-empty>
                </div>
            </section>

            <section class="card">
                <div class="card-body">
                    <x-field :label="__('Notiz (optional)')" name="note">
                        <input id="note" name="note" class="input" maxlength="1000" value="{{ old('note') }}" placeholder="{{ __('z. B. Zustand, Kratzer…') }}">
                    </x-field>
                </div>
                <div class="flex justify-end gap-2 border-t border-line px-4 py-3 sm:px-5">
                    <button class="btn btn-primary" data-return-submit disabled><x-icon name="undo"/><span data-submit-label>{{ __('Rückgabe buchen') }}</span></button>
                </div>
            </section>
        </form>
    </div>

    <template data-icon="x"><x-icon name="x"/></template>
@endsection
