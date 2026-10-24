@extends('layouts.app')
@section('title', __('Übersicht'))

@php $kiosk = auth()->user()->isKiosk(); @endphp

@section('content')
    <x-page-header :title="$kiosk ? __('Willkommen!') : __('Hallo, :name', ['name' => Str::before(auth()->user()->name, ' ')])">
        <x-slot:subtitle>{{ now()->translatedFormat('l, j. F Y') }}</x-slot:subtitle>
        <x-slot:actions>
            <a href="{{ route('calendar') }}" class="btn btn-secondary"><x-icon name="calendar"/>{{ __('Kalender') }}</a>
        </x-slot:actions>
    </x-page-header>

    {{-- Hauptaktionen: groß und eindeutig, auch für Erstbesucher (am Kiosk mit Vormerken) --}}
    <div @class(['mb-6 grid gap-3 sm:grid-cols-3', 'sm:grid-rows-2' => $kiosk])>
        <a href="{{ route('devices.index') }}" @class(['group flex items-center gap-4 rounded-xl bg-accent p-5 text-accent-fg shadow-md transition hover:brightness-110 active:brightness-95 sm:col-span-2 sm:p-6', 'sm:row-span-2 sm:p-8' => $kiosk])>
            <span class="grid size-14 shrink-0 place-items-center rounded-xl bg-white/20"><x-icon name="devices" class="size-7"/></span>
            <span class="min-w-0 flex-1">
                <span @class(['block font-semibold', 'text-xl sm:text-2xl' => ! $kiosk, 'text-2xl sm:text-4xl' => $kiosk])>{{ auth()->user()->can('loans.create') ? __('Gerät ausleihen') : __('Geräte ansehen') }}</span>
                <span class="mt-0.5 block text-sm opacity-90">{{ __('Gerät aussuchen, Zeitraum wählen, fertig.') }}</span>
            </span>
            <x-icon name="chevron-right" class="size-6 shrink-0 transition group-hover:translate-x-1"/>
        </a>
        <a href="{{ route('loans.index') }}" class="card group flex items-center gap-4 p-5 transition hover:border-accent/40 sm:p-6">
            <span class="grid size-12 shrink-0 place-items-center rounded-xl bg-accent-soft text-accent"><x-icon name="undo" class="size-6"/></span>
            <span class="min-w-0 flex-1">
                <span class="block text-lg font-semibold">{{ __('Zurückgeben') }}</span>
                <span class="block text-sm text-muted">{{ __('Laufende Ausleihen') }}</span>
            </span>
        </a>
        @if ($kiosk && auth()->user()->can('reservations.create'))
            <a href="{{ route('devices.index') }}" class="card group flex items-center gap-4 p-5 transition hover:border-accent/40 sm:p-6">
                <span class="grid size-12 shrink-0 place-items-center rounded-xl bg-accent-soft text-accent"><x-icon name="bookmark" class="size-6"/></span>
                <span class="min-w-0 flex-1">
                    <span class="block text-lg font-semibold">{{ __('Vormerken') }}</span>
                    <span class="block text-sm text-muted">{{ __('Gerät für später reservieren') }}</span>
                </span>
            </a>
        @endif
    </div>

    {{-- ID eingeben / scannen --}}
    <form method="POST" action="{{ route('lookup') }}" class="card mb-6 flex flex-col gap-3 p-4 sm:flex-row sm:items-center">
        @csrf
        <div class="flex items-center gap-3 sm:flex-1">
            <span class="grid size-10 shrink-0 place-items-center rounded-lg bg-accent-soft text-accent"><x-icon name="qr" class="size-5"/></span>
            <div class="flex-1">
                <label for="quick-code" class="text-sm font-medium">{{ __('Inventar-ID eingeben') }}</label>
                <input id="quick-code" name="code" class="input mt-1 font-mono uppercase" placeholder="KAB-0042-3" autocomplete="off" autocapitalize="characters" required>
            </div>
        </div>
        <div class="flex gap-2 sm:self-end">
            <button class="btn btn-primary flex-1 sm:flex-none">{{ __('Öffnen') }}</button>
            <a href="{{ route('scan') }}" class="btn btn-secondary flex-1 sm:flex-none"><x-icon name="camera"/>{{ __('Scannen') }}</a>
        </div>
    </form>

    {{-- Kennzahlen --}}
    <div class="mb-6 grid grid-cols-2 gap-3 lg:grid-cols-4">
        @php
            $tiles = [
                ['label' => __('Verliehen'), 'value' => $stats['loaned'], 'sub' => __('von :n Exemplaren', ['n' => $stats['units']]), 'icon' => 'loans', 'tone' => 'text-accent', 'href' => route('loans.index')],
                ['label' => __('Heute fällig'), 'value' => $stats['due_today'], 'sub' => __('Rückgaben'), 'icon' => 'clock', 'tone' => 'text-info', 'href' => route('loans.index')],
                ['label' => __('Überfällig'), 'value' => $stats['overdue'], 'sub' => __('Ausleihen'), 'icon' => 'alert', 'tone' => $stats['overdue'] ? 'text-danger' : 'text-muted', 'href' => route('loans.index')],
                ['label' => __('Zubehör fehlt'), 'value' => $stats['issues'], 'sub' => __('offene Rückgaben'), 'icon' => 'package', 'tone' => $stats['issues'] ? 'text-warn' : 'text-muted', 'href' => route('loans.index').'#issues'],
            ];
        @endphp
        @foreach ($tiles as $tile)
            <a href="{{ $tile['href'] }}" class="card p-4 transition hover:border-accent/40">
                <div class="flex items-center justify-between text-sm text-muted">
                    {{ $tile['label'] }}
                    <x-icon :name="$tile['icon']" class="size-5 {{ $tile['tone'] }}"/>
                </div>
                <p class="mt-2 text-3xl font-semibold tabular-nums {{ $tile['value'] ? $tile['tone'] : '' }}">{{ $tile['value'] }}</p>
                <p class="text-xs text-muted">{{ $tile['sub'] }}</p>
            </a>
        @endforeach
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            {{-- Überfällig & heute fällig --}}
            <section class="card">
                <div class="card-header">
                    <h2 class="card-title">{{ __('Rückgaben') }}</h2>
                    <a href="{{ route('loans.index') }}" class="text-sm text-accent hover:underline">{{ __('Alle Ausleihen') }}</a>
                </div>
                @php $due = $overdue->concat($dueToday)->concat($dueSoon); @endphp
                @if ($due->isEmpty())
                    <x-empty icon="check" :title="__('Nichts fällig')">{{ __('In den nächsten drei Tagen stehen keine Rückgaben an.') }}</x-empty>
                @else
                    <ul class="divide-y divide-line">
                        @foreach ($due as $loan)
                            <li>
                                <a href="{{ route('devices.show', $loan->device_id) }}" class="flex items-center gap-3 px-4 py-3 transition hover:bg-surface-2/60 sm:px-5">
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-sm font-medium">{{ $loan->device?->title }}
                                            @if ($loan->outstanding_quantity > 1)<span class="text-muted">· {{ $loan->outstanding_quantity }}×</span>@endif
                                        </p>
                                        <p class="truncate text-xs text-muted">{{ $loan->borrower_name }}</p>
                                    </div>
                                    @if ($loan->isOverdue())
                                        <span class="badge badge-danger">{{ __('seit :date', ['date' => $loan->loan_end_date->format('d.m.')]) }}</span>
                                    @elseif ($loan->isDueToday())
                                        <span class="badge badge-info">{{ __('heute') }}</span>
                                    @else
                                        <span class="badge badge-muted">{{ $loan->loan_end_date->translatedFormat('D, d.m.') }}</span>
                                    @endif
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>

            {{-- Vormerkungen --}}
            <section class="card">
                <div class="card-header">
                    <h2 class="card-title">{{ __('Vormerkungen (7 Tage)') }}</h2>
                    <a href="{{ route('loans.index') }}#reservations" class="text-sm text-accent hover:underline">{{ __('Alle') }}</a>
                </div>
                @if ($reservations->isEmpty())
                    <x-empty icon="bookmark" :title="__('Keine Vormerkungen')"/>
                @else
                    <ul class="divide-y divide-line">
                        @foreach ($reservations as $r)
                            <li>
                                <a href="{{ route('devices.show', $r->device_id) }}" class="flex items-center gap-3 px-4 py-3 transition hover:bg-surface-2/60 sm:px-5">
                                    <div class="w-14 shrink-0 text-center">
                                        <p class="text-xs text-muted uppercase">{{ $r->start_at->translatedFormat('D') }}</p>
                                        <p class="text-lg leading-tight font-semibold">{{ $r->start_at->format('d.m.') }}</p>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-sm font-medium">{{ $r->quantity }}× {{ $r->device?->title }}</p>
                                        <p class="truncate text-xs text-muted">{{ $r->displayName() }} · {{ $r->start_at->format('H:i') }} – {{ $r->end_at->format('d.m. H:i') }}</p>
                                    </div>
                                    @if ($r->start_at->isPast())
                                        <span class="badge badge-warn">{{ __('abholbereit') }}</span>
                                    @endif
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>
        </div>

        <div class="space-y-6">
            {{-- Raumbuchungen --}}
            <section class="card">
                <div class="card-header">
                    <h2 class="card-title">{{ __('Räume heute & morgen') }}</h2>
                    <a href="{{ route('bookings.index') }}" class="text-sm text-accent hover:underline">{{ __('Alle') }}</a>
                </div>
                @if ($bookings->isEmpty())
                    <x-empty icon="door" :title="__('Keine Buchungen')"/>
                @else
                    <ul class="divide-y divide-line">
                        @foreach ($bookings as $b)
                            <li class="px-4 py-3 sm:px-5">
                                <p class="text-sm font-medium">{{ $b->room?->name }}</p>
                                <p class="text-xs text-muted">
                                    {{ $b->start_at->isToday() ? __('Heute') : $b->start_at->translatedFormat('D d.m.') }},
                                    {{ $b->start_at->format('H:i') }}–{{ $b->end_at->format($b->end_at->isSameDay($b->start_at) ? 'H:i' : 'd.m. H:i') }}
                                    · {{ $b->displayName() }}
                                </p>
                                @if ($b->purpose)<p class="mt-0.5 truncate text-xs text-muted">{{ $b->purpose }}</p>@endif
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>

            {{-- Offene Rückgabe-Probleme --}}
            @if ($issues->isNotEmpty())
                <section class="card border-warn/40">
                    <div class="card-header">
                        <h2 class="card-title flex items-center gap-2 text-warn"><x-icon name="alert"/>{{ __('Zubehör fehlt') }}</h2>
                    </div>
                    <ul class="divide-y divide-line">
                        @foreach ($issues as $loan)
                            <li>
                                <a href="{{ route('devices.show', $loan->device_id) }}" class="block px-4 py-3 hover:bg-surface-2/60 sm:px-5">
                                    <p class="text-sm font-medium">{{ $loan->device?->title }} · {{ $loan->borrower_name }}</p>
                                    <p class="text-xs text-muted">{{ collect($loan->return_issues)->map(fn ($i) => $i['missing'].'× '.$i['name'])->join(', ') }}</p>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif

            {{-- Eigene Vormerkungen --}}
            @if ($myReservations->isNotEmpty() && ! $kiosk)
                <section class="card">
                    <div class="card-header"><h2 class="card-title">{{ __('Meine Vormerkungen') }}</h2></div>
                    <ul class="divide-y divide-line">
                        @foreach ($myReservations as $r)
                            <li>
                                <a href="{{ route('devices.show', $r->device_id) }}" class="block px-4 py-3 hover:bg-surface-2/60 sm:px-5">
                                    <p class="text-sm font-medium">{{ $r->quantity }}× {{ $r->device?->title }}</p>
                                    <p class="text-xs text-muted">{{ $r->start_at->format('d.m.Y H:i') }} – {{ $r->end_at->format('d.m.Y H:i') }}</p>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif
        </div>
    </div>
@endsection
