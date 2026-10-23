@extends('layouts.app')
@section('title', $device->title)

@php
    $form = old('_form');
    $canLoan = Gate::allows('loans.create');
    $canReturn = Gate::allows('loans.return');
    $accessoryItems = $device->accessories->map(fn ($a) => ['key' => $a->id, 'name' => $a->name, 'quantity' => $a->quantity])->all();
    $defaultDays = (int) $settings->get('loans.default_days');
    $here = '/'.request()->path();
@endphp

@section('content')
    <x-page-header :title="$device->title" :back="route('devices.index')">
        <x-slot:subtitle>
            <span class="flex flex-wrap items-center gap-2">
                <span class="id-chip">{{ $device->inventory_number }}</span>
                @if ($device->category)<span>{{ $device->category->name }}</span>@endif
                <x-device-status :device="$device"/>
            </span>
        </x-slot:subtitle>
        <x-slot:actions>
            @if ($canLoan && $device->available_quantity > 0)
                <button type="button" class="btn btn-primary" data-dialog="loan-dialog"><x-icon name="loans"/>{{ __('Ausleihen') }}</button>
            @endif
            @can('reservations.create')
                <a href="{{ route('reservations.create', $device) }}" class="btn btn-secondary"><x-icon name="bookmark"/>{{ __('Vormerken') }}</a>
            @endcan
            <button type="button" class="btn btn-secondary btn-icon" data-dialog="qr-dialog" title="{{ __('QR-Code') }}" aria-label="{{ __('QR-Code') }}"><x-icon name="qr"/></button>
            @can('devices.manage')
                <a href="{{ route('devices.edit', $device) }}" class="btn btn-secondary btn-icon" title="{{ __('Bearbeiten') }}" aria-label="{{ __('Bearbeiten') }}"><x-icon name="edit"/></a>
            @endcan
        </x-slot:actions>
    </x-page-header>

    @if ($preselectUnit)
        <div class="card mb-6 flex items-center gap-3 border-accent/40 bg-accent-soft p-4">
            <x-icon name="qr" class="size-5 text-accent"/>
            <p class="flex-1 text-sm">
                {{ __('Gescannt:') }} <span class="id-chip">{{ $device->unitLabel($preselectUnit) }}</span>
                @isset($loanedUnits[$preselectUnit])
                    – {{ __('verliehen an :name', ['name' => $loanedUnits[$preselectUnit]->borrower_name]) }}
                @endisset
            </p>
            @isset($loanedUnits[$preselectUnit])
                @if ($canReturn)
                    <button type="button" class="btn btn-primary btn-sm" data-dialog="return-dialog-{{ $loanedUnits[$preselectUnit]->id }}">{{ __('Zurücknehmen') }}</button>
                @endif
            @else
                @if ($canLoan && $device->available_quantity > 0)
                    <button type="button" class="btn btn-primary btn-sm" data-dialog="loan-dialog">{{ __('Ausleihen') }}</button>
                @endif
            @endisset
        </div>
    @endif

    <div class="grid gap-6 lg:grid-cols-3">
        {{-- Linke Spalte: Gerät --}}
        <div class="space-y-6">
            <div class="card overflow-hidden">
                @if ($device->image_url)
                    <a href="{{ $device->image_url }}" target="_blank" rel="noopener">
                        <img src="{{ $device->image_url }}" alt="{{ $device->title }}" class="aspect-[4/3] w-full bg-surface-2 object-contain">
                    </a>
                @else
                    <div class="grid aspect-[4/3] place-items-center bg-surface-2 text-muted/50"><x-icon name="image" class="size-12"/></div>
                @endif
                <div class="card-body space-y-4">
                    @if ($device->description)
                        <p class="text-sm whitespace-pre-line">{{ $device->description }}</p>
                    @endif
                    <dl class="grid grid-cols-2 gap-3 text-sm">
                        <div><dt class="text-muted">{{ __('Bestand') }}</dt><dd class="font-medium">{{ $device->total_quantity }}</dd></div>
                        <div><dt class="text-muted">{{ __('Frei') }}</dt><dd class="font-medium">{{ $device->available_quantity }}</dd></div>
                    </dl>
                </div>
            </div>

            @if ($device->accessories->isNotEmpty())
                <div class="card">
                    <div class="card-header"><h2 class="card-title">{{ __('Zubehör je Exemplar') }}</h2></div>
                    <ul class="divide-y divide-line text-sm">
                        @foreach ($device->accessories as $a)
                            <li class="flex items-center justify-between px-4 py-2.5 sm:px-5">{{ $a->name }}<span class="text-muted">{{ $a->quantity }}×</span></li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if ($device->total_quantity > 1)
                <div class="card">
                    <div class="card-header"><h2 class="card-title">{{ __('Exemplare') }}</h2></div>
                    <ul class="divide-y divide-line text-sm">
                        @foreach (range(1, $device->total_quantity) as $n)
                            <li @class(['flex items-center justify-between gap-2 px-4 py-2 sm:px-5', 'bg-accent-soft' => $preselectUnit === $n])>
                                <span class="id-chip">{{ $device->unitLabel($n) }}</span>
                                @isset($loanedUnits[$n])
                                    <span class="truncate text-xs text-muted">{{ $loanedUnits[$n]->borrower_name }}</span>
                                @else
                                    <span class="text-xs text-muted">—</span>
                                @endisset
                            </li>
                        @endforeach
                    </ul>
                    <p class="px-4 py-2 text-xs text-muted sm:px-5">{{ __('Nur Exemplare, die bei der Ausleihe angegeben wurden, sind hier zugeordnet.') }}</p>
                </div>
            @endif
        </div>

        {{-- Rechte Spalte: Ausleihen, Vormerkungen, Verlauf --}}
        <div class="space-y-6 lg:col-span-2">
            @if ($openIssues->isNotEmpty())
                <section class="card border-warn/40">
                    <div class="card-header"><h2 class="card-title flex items-center gap-2 text-warn"><x-icon name="alert"/>{{ __('Fehlendes Zubehör') }}</h2></div>
                    <ul class="divide-y divide-line">
                        @foreach ($openIssues as $loan)
                            <li class="flex flex-col gap-2 px-4 py-3 sm:flex-row sm:items-center sm:px-5">
                                <div class="flex-1 text-sm">
                                    <p class="font-medium">{{ $loan->borrower_name }} <span class="font-normal text-muted">· {{ __('Rückgabe am :date', ['date' => $loan->returned_at?->format('d.m.Y')]) }}</span></p>
                                    <p class="text-muted">{{ collect($loan->return_issues)->map(fn ($i) => $i['missing'].'× '.$i['name'])->join(', ') }}</p>
                                </div>
                                @if ($canReturn)
                                    <form method="POST" action="{{ route('loans.resolve', $loan) }}" class="flex gap-2">
                                        @csrf
                                        <input type="hidden" name="redirect" value="{{ $here }}">
                                        <input name="note" class="input py-1.5" placeholder="{{ __('Notiz (optional)') }}" maxlength="1000">
                                        <button class="btn btn-secondary btn-sm"><x-icon name="check"/>{{ __('Erledigt') }}</button>
                                    </form>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif

            <section class="card">
                <div class="card-header">
                    <h2 class="card-title">{{ __('Aktuelle Ausleihen') }}</h2>
                    <span class="text-xs text-muted">{{ __(':n von :t verliehen', ['n' => $device->loaned_quantity, 't' => $device->total_quantity]) }}</span>
                </div>
                @if ($activeLoans->isEmpty())
                    <x-empty icon="check" :title="__('Nicht verliehen')">
                        @if ($canLoan)
                            <button type="button" class="btn btn-primary btn-sm mt-3" data-dialog="loan-dialog">{{ __('Jetzt ausleihen') }}</button>
                        @endif
                    </x-empty>
                @else
                    <ul class="divide-y divide-line">
                        @foreach ($activeLoans as $loan)
                            <li class="px-4 py-4 sm:px-5">
                                <div class="flex flex-col gap-3 sm:flex-row sm:items-start">
                                    <div class="min-w-0 flex-1">
                                        <p class="font-medium">
                                            {{ $loan->borrower_name }}
                                            <span class="font-normal text-muted">· {{ $loan->outstanding_quantity }}×</span>
                                            @if ($loan->returned_quantity > 0)
                                                <span class="badge badge-muted">{{ __(':n zurück', ['n' => $loan->returned_quantity]) }}</span>
                                            @endif
                                        </p>
                                        <p class="mt-0.5 text-sm text-muted">
                                            {{ $loan->loan_start_date->format('d.m.Y') }} – {{ $loan->loan_end_date->format('d.m.Y') }}
                                            @if ($loan->isOverdue())
                                                <span class="badge badge-danger ml-1">{{ __('überfällig') }}</span>
                                            @elseif ($loan->isDueToday())
                                                <span class="badge badge-info ml-1">{{ __('heute fällig') }}</span>
                                            @endif
                                            @if ($loan->extension_count)
                                                <span class="badge badge-muted ml-1">{{ trans_choice(':count× verlängert|:count× verlängert', $loan->extension_count) }}</span>
                                            @endif
                                        </p>
                                        @if ($loan->loan_purpose)<p class="mt-1 text-sm">{{ $loan->loan_purpose }}</p>@endif
                                        @if ($loan->units->whereNull('returned_at')->isNotEmpty())
                                            <p class="mt-2 flex flex-wrap gap-1">
                                                @foreach ($loan->units->whereNull('returned_at') as $unit)
                                                    <span class="id-chip">{{ $device->unitLabel($unit->unit_no) }}</span>
                                                @endforeach
                                            </p>
                                        @endif
                                        @if ($loan->accessories)
                                            <p class="mt-2 text-xs text-muted">
                                                {{ __('Zubehör:') }}
                                                {{ collect($loan->accessories)->map(fn ($a) => ($a['checked'] ? '' : '✗ ').$a['quantity'].'× '.$a['name'])->join(', ') }}
                                            </p>
                                        @endif
                                        @if ($loan->notes)<p class="mt-1 text-xs whitespace-pre-line text-muted">{{ $loan->notes }}</p>@endif
                                        <p class="mt-1 text-xs text-muted">{{ __('ausgegeben von :name', ['name' => $loan->loanedBy?->name ?? '—']) }}</p>
                                    </div>
                                    @if ($canReturn)
                                        <div class="flex shrink-0 gap-2">
                                            <button type="button" class="btn btn-primary btn-sm" data-dialog="return-dialog-{{ $loan->id }}"><x-icon name="undo"/>{{ __('Rückgabe') }}</button>
                                            <button type="button" class="btn btn-secondary btn-sm" data-dialog="extend-dialog-{{ $loan->id }}"><x-icon name="calendar-plus"/>{{ __('Verlängern') }}</button>
                                        </div>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>

            <section class="card" id="reservations">
                <div class="card-header">
                    <h2 class="card-title">{{ __('Vormerkungen') }}</h2>
                    @can('reservations.create')
                        <a href="{{ route('reservations.create', $device) }}" class="text-sm text-accent hover:underline">{{ __('Neu') }}</a>
                    @endcan
                </div>
                @if ($reservations->isEmpty())
                    <x-empty icon="bookmark" :title="__('Keine Vormerkungen')"/>
                @else
                    <ul class="divide-y divide-line">
                        @foreach ($reservations as $r)
                            <li class="flex flex-col gap-3 px-4 py-3 sm:flex-row sm:items-center sm:px-5">
                                <div class="min-w-0 flex-1 text-sm">
                                    <p class="font-medium">{{ $r->quantity }}× · {{ $r->displayName() }}</p>
                                    <p class="text-muted">{{ $r->start_at->format('d.m.Y H:i') }} – {{ $r->end_at->format('d.m.Y H:i') }}
                                        @if ($r->end_at->isPast())<span class="badge badge-warn ml-1">{{ __('abgelaufen') }}</span>@endif
                                    </p>
                                    @if ($r->purpose)<p class="text-muted">{{ $r->purpose }}</p>@endif
                                    <p class="text-xs text-muted">{{ __('vorgemerkt von :name', ['name' => $r->user?->name ?? '—']) }}</p>
                                </div>
                                <div class="flex gap-2">
                                    @can('convert', $r)
                                        <button type="button" class="btn btn-primary btn-sm" data-dialog="convert-dialog-{{ $r->id }}"><x-icon name="convert"/>{{ __('Ausgeben') }}</button>
                                    @endcan
                                    @can('cancel', $r)
                                        <form method="POST" action="{{ route('reservations.destroy', $r) }}" data-confirm="{{ __('Vormerkung widerrufen?') }}">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-danger-ghost btn-sm">{{ __('Widerrufen') }}</button>
                                        </form>
                                    @endcan
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>

            <section class="card">
                <div class="card-header"><h2 class="card-title flex items-center gap-2"><x-icon name="history"/>{{ __('Verlauf') }}</h2></div>
                @if ($history->isEmpty())
                    <x-empty icon="history" :title="__('Noch keine Einträge')"/>
                @else
                    <ol class="divide-y divide-line text-sm">
                        @foreach ($history as $log)
                            <li class="flex gap-3 px-4 py-2.5 sm:px-5">
                                <span class="w-28 shrink-0 text-xs text-muted tabular-nums">{{ $log->created_at?->format('d.m.Y H:i') }}</span>
                                <span class="min-w-0 flex-1">
                                    <span class="font-medium">{{ $log->description() }}</span>
                                    @if (! empty($log->properties['borrower']))· {{ $log->properties['borrower'] }}@endif
                                    @if (! empty($log->properties['quantity']))<span class="text-muted">({{ $log->properties['quantity'] }}×)</span>@endif
                                    @if (! empty($log->properties['until']))<span class="text-muted">{{ __('bis :date', ['date' => $log->properties['until']]) }}</span>@endif
                                    @if (! empty($log->properties['missing']))<span class="text-warn">– {{ __('fehlt:') }} {{ implode(', ', (array) $log->properties['missing']) }}</span>@endif
                                    <span class="block text-xs text-muted">{{ $log->user_name ?? '—' }}</span>
                                </span>
                            </li>
                        @endforeach
                    </ol>
                @endif
            </section>
        </div>
    </div>

    {{-- ---------------------------------------------------------------- --}}
    {{-- Dialoge                                                           --}}
    {{-- ---------------------------------------------------------------- --}}

    @if ($canLoan)
        <x-modal id="loan-dialog" :title="__(':title ausleihen', ['title' => $device->title])" :open-on-load="$form === 'loan' || ($preselectUnit && ! isset($loanedUnits[$preselectUnit]) && $device->available_quantity > 0)">
            <form method="POST" action="{{ route('loans.store', $device) }}" class="space-y-4" x-data="loanForm" data-quantity="{{ old('quantity', 1) }}" @change="count">
                @csrf
                <input type="hidden" name="_form" value="loan">

                <x-field :label="__('Ausleihende Person')" name="borrower_name">
                    <input id="borrower_name" name="borrower_name" class="input" value="{{ $form === 'loan' ? old('borrower_name') : '' }}" required maxlength="255" autofocus autocomplete="off"
                           list="borrower-suggestions" placeholder="{{ __('Name, Klasse oder Gruppe') }}">
                </x-field>

                <div class="grid grid-cols-2 gap-3">
                    <x-field :label="__('Von')" name="loan_start_date">
                        <input id="loan_start_date" type="date" name="loan_start_date" class="input" value="{{ old('loan_start_date', today()->toDateString()) }}" required>
                    </x-field>
                    <x-field :label="__('Rückgabe bis')" name="loan_end_date">
                        <input id="loan_end_date" type="date" name="loan_end_date" class="input" value="{{ old('loan_end_date', today()->addDays($defaultDays)->toDateString()) }}" required>
                    </x-field>
                </div>

                @if ($device->total_quantity > 1)
                    <x-field :label="__('Menge')" name="quantity" :hint="__(':n frei', ['n' => $device->available_quantity])">
                        <input id="quantity" type="number" name="quantity" class="input w-28" min="1" max="{{ $device->available_quantity }}"
                               x-model="quantity" :disabled="usesUnits" :readonly="usesUnits">
                    </x-field>
                    <x-field :label="__('Exemplare (optional)')" name="units" :hint="__('Exemplare angeben, um doppelte Ausgaben zu erkennen.')">
                        @include('devices.partials.unit-grid', [
                            'selectable' => null,
                            'busy' => $loanedUnits,
                            'selected' => array_map('intval', (array) old('units', $preselectUnit ? [$preselectUnit] : [])),
                        ])
                    </x-field>
                @else
                    <input type="hidden" name="quantity" value="1">
                @endif

                @include('devices.partials.accessory-checklist', [
                    'items' => $accessoryItems,
                    'legend' => __('Zubehör mitgegeben'),
                    'hint' => $device->total_quantity > 1 ? __('Mengen gelten je Exemplar.') : null,
                ])

                <x-field :label="__('Zweck (optional)')" name="loan_purpose">
                    <input id="loan_purpose" name="loan_purpose" class="input" value="{{ $form === 'loan' ? old('loan_purpose') : '' }}" maxlength="255">
                </x-field>
                <x-field :label="__('Notiz (optional)')" name="notes">
                    <textarea id="notes" name="notes" rows="2" class="input" maxlength="2000">{{ $form === 'loan' ? old('notes') : '' }}</textarea>
                </x-field>

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" class="btn btn-secondary" data-dialog-close>{{ __('Abbrechen') }}</button>
                    <button class="btn btn-primary"><x-icon name="check"/>{{ __('Ausleihen') }}</button>
                </div>
            </form>
        </x-modal>
        <datalist id="borrower-suggestions">
            @foreach ($borrowers as $name)
                <option value="{{ $name }}"></option>
            @endforeach
        </datalist>
    @endif

    @if ($canReturn)
        @foreach ($activeLoans as $loan)
            @php $outstandingUnits = $loan->outstandingUnitNumbers(); @endphp
            <x-modal :id="'return-dialog-'.$loan->id" :title="__('Rückgabe: :name', ['name' => $loan->borrower_name])" :open-on-load="$form === 'return-'.$loan->id">
                <form method="POST" action="{{ route('loans.return', $loan) }}" class="space-y-4" x-data="returnForm" @change="count" @input="count"
                      data-mode="{{ $form === 'return-'.$loan->id ? old('mode', 'all') : 'all' }}" data-outstanding="{{ $loan->outstanding_quantity }}">
                    @csrf
                    <input type="hidden" name="_form" value="return-{{ $loan->id }}">
                    <input type="hidden" name="redirect" value="{{ $here }}">

                    <p class="text-sm text-muted">{{ trans_choice('Offen: :count Exemplar|Offen: :count Exemplare', $loan->outstanding_quantity) }} · {{ __('bis :date', ['date' => $loan->loan_end_date->format('d.m.Y')]) }}</p>

                    @if ($loan->outstanding_quantity > 1)
                        <div class="grid gap-2 sm:grid-cols-3">
                            <label class="flex items-center gap-2 rounded-lg border border-line p-2.5 text-sm has-checked:border-accent has-checked:bg-accent-soft">
                                <input type="radio" name="mode" value="all" x-model="mode" class="accent-[var(--accent)]"> {{ __('Alles') }}
                            </label>
                            @if ($outstandingUnits)
                                {{-- Mit Exemplar-Nummern verliehen: Teilrückgabe nur über die Exemplare --}}
                                <label class="flex items-center gap-2 rounded-lg border border-line p-2.5 text-sm has-checked:border-accent has-checked:bg-accent-soft">
                                    <input type="radio" name="mode" value="units" x-model="mode" class="accent-[var(--accent)]"> {{ __('Exemplare') }}
                                </label>
                            @else
                                <label class="flex items-center gap-2 rounded-lg border border-line p-2.5 text-sm has-checked:border-accent has-checked:bg-accent-soft">
                                    <input type="radio" name="mode" value="quantity" x-model="mode" class="accent-[var(--accent)]"> {{ __('Teilmenge') }}
                                </label>
                            @endif
                        </div>
                        <div x-show="isQuantity" x-cloak>
                            <x-field :label="__('Anzahl zurück')" name="quantity">
                                <input type="number" name="quantity" class="input w-28" min="1" max="{{ $loan->outstanding_quantity }}" value="{{ old('quantity', 1) }}">
                            </x-field>
                        </div>
                        @if ($outstandingUnits)
                            <div x-show="isUnits" x-cloak>
                                <x-field :label="__('Zurückgegebene Exemplare')" name="units">
                                    @include('devices.partials.unit-grid', [
                                        'selectable' => $outstandingUnits,
                                        'busy' => [],
                                        'selected' => $preselectUnit && in_array($preselectUnit, $outstandingUnits, true) ? [$preselectUnit] : [],
                                    ])
                                </x-field>
                            </div>
                        @endif
                    @endif

                    @php
                        $given = collect($loan->accessories ?? [])->filter(fn ($a) => $a['checked'] ?? true);
                    @endphp
                    @if ($given->isNotEmpty())
                        {{-- Nur wenn die Rückgabe die Ausleihe abschließt; sonst deaktiviert,
                             damit keine (unsichtbaren) "fehlt"-Häkchen mitgeschickt werden --}}
                        <div x-show="completes">
                            <fieldset class="contents" :disabled="!completes" data-return-checklist>
                                @include('devices.partials.accessory-checklist', [
                                    'items' => $given->map(fn ($a, $i) => ['key' => $i, 'name' => $a['name'], 'quantity' => $a['quantity']])->values()->all(),
                                    'legend' => __('Zubehör vollständig zurück?'),
                                    'checked' => false,
                                    'hint' => __('Nicht angehaktes Zubehör wird als fehlend vermerkt.'),
                                ])
                            </fieldset>
                        </div>
                        <p x-show="completes" class="error">{{ $errors->first('accessories') }}</p>
                    @endif

                    <x-field :label="__('Notiz (optional)')" name="note">
                        <input name="note" class="input" maxlength="1000" placeholder="{{ __('z. B. Zustand, Kratzer…') }}">
                    </x-field>

                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" class="btn btn-secondary" data-dialog-close>{{ __('Abbrechen') }}</button>
                        <button class="btn btn-primary"><x-icon name="undo"/>{{ __('Rückgabe buchen') }}</button>
                    </div>
                </form>
            </x-modal>

            <x-modal :id="'extend-dialog-'.$loan->id" :title="__('Verlängern: :name', ['name' => $loan->borrower_name])" :open-on-load="$form === 'extend-'.$loan->id">
                <form method="POST" action="{{ route('loans.extend', $loan) }}" class="space-y-4">
                    @csrf
                    <input type="hidden" name="_form" value="extend-{{ $loan->id }}">
                    <input type="hidden" name="redirect" value="{{ $here }}">
                    <p class="text-sm text-muted">{{ __('Bisher bis :date', ['date' => $loan->loan_end_date->format('d.m.Y')]) }}</p>
                    <x-field :label="__('Neues Rückgabedatum')" name="loan_end_date">
                        <input type="date" name="loan_end_date" class="input" min="{{ max($loan->loan_end_date->copy()->addDay(), today())->toDateString() }}"
                               value="{{ old('loan_end_date', max($loan->loan_end_date->copy(), today())->addDays($defaultDays)->toDateString()) }}" required>
                    </x-field>
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" class="btn btn-secondary" data-dialog-close>{{ __('Abbrechen') }}</button>
                        <button class="btn btn-primary"><x-icon name="calendar-plus"/>{{ __('Verlängern') }}</button>
                    </div>
                </form>
            </x-modal>
        @endforeach
    @endif

    @foreach ($reservations as $r)
        @can('convert', $r)
            <x-modal :id="'convert-dialog-'.$r->id" :title="__('Vormerkung ausgeben')" :open-on-load="$form === 'convert-'.$r->id">
                <form method="POST" action="{{ route('reservations.convert', $r) }}" class="space-y-4" x-data="loanForm" data-quantity="{{ $r->quantity }}">
                    @csrf
                    <input type="hidden" name="_form" value="convert-{{ $r->id }}">
                    <p class="text-sm text-muted">{{ $r->quantity }}× · {{ __('Rückgabe bis :date', ['date' => ($r->end_at->isPast() ? today() : $r->end_at)->format('d.m.Y')]) }}</p>
                    <x-field :label="__('Ausleihende Person')" name="borrower_name">
                        <input name="borrower_name" class="input" value="{{ old('borrower_name', $r->displayName()) }}" maxlength="255">
                    </x-field>
                    @if ($device->total_quantity > 1)
                        <x-field :label="__('Exemplare (optional, genau :n)', ['n' => $r->quantity])" name="units">
                            @include('devices.partials.unit-grid', ['selectable' => null, 'busy' => $loanedUnits, 'selected' => []])
                        </x-field>
                    @endif
                    @include('devices.partials.accessory-checklist', ['items' => $accessoryItems, 'legend' => __('Zubehör mitgegeben')])
                    @error('reservation')<p class="error">{{ $message }}</p>@enderror
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" class="btn btn-secondary" data-dialog-close>{{ __('Abbrechen') }}</button>
                        <button class="btn btn-primary"><x-icon name="convert"/>{{ __('Als Ausleihe ausgeben') }}</button>
                    </div>
                </form>
            </x-modal>
        @endcan
    @endforeach

    <x-modal id="qr-dialog" :title="__('QR-Code')" size="max-w-sm">
        <div class="text-center">
            <img src="{{ route('devices.qr', $device) }}" alt="QR {{ $device->inventory_number }}" class="mx-auto size-56 rounded-lg bg-white p-3">
            <p class="mt-3 font-mono text-lg font-semibold">{{ $device->inventory_number }}</p>
            <p class="text-sm text-muted">{{ $device->title }}</p>
            @can('labels.print')
                <a href="{{ route('labels.index', ['devices' => [$device->id]]) }}" class="btn btn-secondary mt-4"><x-icon name="printer"/>{{ __('Etiketten drucken') }}</a>
            @endcan
        </div>
    </x-modal>

    @can('devices.manage')
        <div class="mt-8 flex justify-end">
            <form method="POST" action="{{ route('devices.destroy', $device) }}" data-confirm="{{ __('Gerät wirklich löschen? Die Historie bleibt erhalten.') }}">
                @csrf @method('DELETE')
                <button class="btn btn-danger-ghost btn-sm"><x-icon name="trash"/>{{ __('Gerät löschen') }}</button>
            </form>
        </div>
    @endcan
@endsection
