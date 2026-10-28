@extends('layouts.app')
@section('title', __('Übersicht'))

{{-- Startseite der Raumplanung: nur Räume, Buchungen und Kalender – keine Geräte --}}
@php $canBook = auth()->user()->can('rooms.book'); @endphp

@section('content')
    <x-page-header :title="__('Hallo, :name', ['name' => Str::before(auth()->user()->name, ' ')])">
        <x-slot:subtitle>{{ now()->translatedFormat(__('l, j. F Y')) }}</x-slot:subtitle>
    </x-page-header>

    {{-- Hauptaktionen: groß und eindeutig --}}
    <div class="mb-6 grid gap-3 sm:grid-cols-3">
        @if ($canBook)
            <a href="{{ route('bookings.new') }}" class="group flex items-center gap-4 hero-tile rounded-2xl p-5 transition hover:brightness-110 active:brightness-95 sm:col-span-2 sm:p-6">
                <span class="grid size-14 shrink-0 place-items-center rounded-xl bg-white/20 ring-1 ring-white/25 ring-inset"><x-icon name="calendar-plus" class="size-7"/></span>
                <span class="min-w-0 flex-1">
                    <span class="block text-xl font-semibold sm:text-2xl">{{ __('Neue Buchung') }}</span>
                    <span class="mt-0.5 block text-sm opacity-90">{{ __('Saal wählen, Zeitraum festlegen, fertig.') }}</span>
                </span>
                <x-icon name="chevron-right" class="size-6 shrink-0 transition group-hover:translate-x-1"/>
            </a>
        @endif
        <a href="{{ route('calendar') }}" @class(['card group flex items-center gap-4 p-5 transition hover:border-accent/40 sm:p-6', 'sm:col-span-3' => ! $canBook])>
            <span class="icon-tile size-12"><x-icon name="calendar" class="size-6"/></span>
            <span class="min-w-0 flex-1">
                <span class="block text-lg font-semibold">{{ __('Kalender') }}</span>
                <span class="block text-sm text-muted">{{ __('Alle Buchungen im Überblick') }}</span>
            </span>
        </a>
    </div>

    {{-- Säle: jetzt frei oder belegt --}}
    @if ($rooms->isNotEmpty())
        <div class="mb-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($rooms as $room)
                @php $current = $room->bookings->first(); @endphp
                <div class="card flex items-center gap-3 p-4">
                    <span class="cal-dot cal-dot-filled size-3 {{ $room->colorClass() }}" aria-hidden="true"></span>
                    <div class="min-w-0 flex-1">
                        <p class="flex items-center gap-2 font-semibold">
                            <span class="truncate">{{ $room->name }}</span>
                            @if ($current)
                                <span class="badge badge-danger">{{ __('belegt') }}</span>
                            @else
                                <span class="badge badge-ok">{{ __('frei') }}</span>
                            @endif
                        </p>
                        <p class="truncate text-xs text-muted">
                            @if ($current)
                                {{ __('bis :time · :name', ['time' => $current->end_at->format('H:i'), 'name' => $current->purpose ?: $current->displayName()]) }}
                            @else
                                {{ $room->location }}
                            @endif
                        </p>
                    </div>
                    @if ($canBook)
                        <a href="{{ route('bookings.create', $room) }}" class="btn btn-secondary btn-sm">{{ __('Buchen') }}</a>
                    @endif
                </div>
            @endforeach
        </div>
    @endif

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <section class="card">
                <div class="card-header flex items-center justify-between">
                    <h2 class="card-title">{{ __('Heute') }}</h2>
                    <a href="{{ route('bookings.index') }}" class="text-sm text-accent hover:underline">{{ __('Alle Buchungen') }}</a>
                </div>
                @if ($today->isEmpty())
                    <x-empty icon="calendar" :title="__('Heute ist nichts gebucht')"/>
                @else
                    <ul class="divide-y divide-line">
                        @foreach ($today as $b)
                            @include('bookings.partials.row', ['b' => $b])
                        @endforeach
                    </ul>
                @endif
            </section>

            <section class="card">
                <div class="card-header"><h2 class="card-title">{{ __('Die nächsten 7 Tage') }}</h2></div>
                @if ($upcoming->isEmpty())
                    <x-empty icon="calendar" :title="__('Keine Buchungen')"/>
                @else
                    @foreach ($upcoming as $day => $items)
                        <p class="border-t border-line bg-surface-2/60 px-4 py-1.5 text-xs font-semibold tracking-wide text-muted uppercase first:border-t-0 sm:px-5">
                            {{ \Illuminate\Support\Carbon::parse($day)->translatedFormat('l, d.m.') }}
                        </p>
                        <ul class="divide-y divide-line">
                            @foreach ($items as $b)
                                @include('bookings.partials.row', ['b' => $b])
                            @endforeach
                        </ul>
                    @endforeach
                @endif
            </section>
        </div>

        <section class="card h-fit">
            <div class="card-header"><h2 class="card-title">{{ __('Meine Buchungen') }}</h2></div>
            @if ($mine->isEmpty())
                <x-empty icon="bookmark" :title="__('Noch keine eigenen Buchungen')"/>
            @else
                <ul class="divide-y divide-line">
                    @foreach ($mine as $b)
                        <li>
                            <a href="{{ route('bookings.edit', $b) }}" class="block px-4 py-3 transition hover:bg-surface-2 sm:px-5">
                                <p class="truncate text-sm font-medium">{{ $b->purpose }}</p>
                                <p class="text-xs text-muted">{{ $b->room?->name }} · {{ $b->start_at->isToday() ? __('Heute') : $b->start_at->translatedFormat('D d.m.') }}, {{ $b->timeRange() }}</p>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    </div>
@endsection
