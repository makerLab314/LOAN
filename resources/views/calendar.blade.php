@extends('layouts.app')
@section('title', __('Kalender'))

@php
    // Raumplanung: nur Raumbuchungen, deshalb ohne Auswahl der Arten
    $devices = Gate::allows('devices.view');
    $types = [
        ['value' => 'rooms', 'class' => 'ev-room', 'label' => __('Raumbuchungen')],
        ['value' => 'reservations', 'class' => 'ev-reservation', 'label' => __('Vormerkungen')],
        ['value' => 'loans', 'class' => 'ev-loan', 'label' => __('Ausleihen')],
    ];
@endphp

@section('content')
    <x-page-header :title="__('Kalender')">
        <x-slot:subtitle>{{ $devices ? __('Räume, Vormerkungen und Ausleihen auf einen Blick.') : __('Alle Raumbuchungen auf einen Blick.') }}</x-slot:subtitle>
        @can('rooms.book')
            <x-slot:actions>
                <a href="{{ route('bookings.new') }}" class="btn btn-primary"><x-icon name="calendar-plus"/>{{ __('Neue Buchung') }}</a>
            </x-slot:actions>
        @endcan
    </x-page-header>

    <div class="mb-4 flex flex-col gap-3 lg:flex-row lg:items-center" data-calendar-filters>
        @if ($devices)
            <div class="flex flex-wrap gap-2" role="group" aria-label="{{ __('Anzeigen') }}">
                @foreach ($types as $type)
                    <label class="cal-chip {{ $type['class'] }}">
                        <input type="checkbox" name="types[]" value="{{ $type['value'] }}" class="sr-only" checked>
                        <span class="cal-dot" aria-hidden="true"></span>{{ $type['label'] }}
                    </label>
                @endforeach
                <span class="cal-chip ev-overdue pointer-events-none border-dashed" title="{{ __('Teil der Ausleihen') }}">
                    <span class="cal-dot cal-dot-filled" aria-hidden="true"></span>{{ __('Überfällig') }}
                </span>
            </div>
        @endif
        @if ($rooms->count() > 1)
            {{-- Säle in ihrer Kalenderfarbe; alle an = kein Filter --}}
            <div @class(['flex flex-wrap items-center gap-2', 'lg:ml-auto lg:border-l lg:border-line lg:pl-4' => $devices]) role="group" aria-label="{{ __('Räume') }}" data-room-filter>
                @foreach ($rooms as $room)
                    <label class="cal-chip {{ $room->colorClass() }}">
                        <input type="checkbox" name="rooms[]" value="{{ $room->id }}" class="sr-only" checked>
                        <span class="cal-dot" aria-hidden="true"></span>{{ $room->name }}
                    </label>
                @endforeach
            </div>
        @endif
    </div>

    <div class="card p-3 sm:p-5">
        <div data-calendar data-events="{{ route('calendar.events') }}"></div>
    </div>

    <dialog id="event-dialog" class="modal max-w-sm overflow-hidden">
        <div class="cal-bar h-1.5"></div>
        <div class="p-5">
            <p class="cal-kind" data-field="kind"></p>
            <h2 class="mt-2 text-lg leading-snug font-semibold" data-field="title"></h2>
            <p class="mt-3 flex items-start gap-2 text-sm"><x-icon name="clock" class="mt-0.5 size-4 shrink-0 text-muted"/><span data-field="time"></span></p>
            <p class="mt-1.5 flex items-start gap-2 text-sm text-muted"><x-icon name="info" class="mt-0.5 size-4 shrink-0"/><span data-field="detail"></span></p>
            <div class="mt-5 flex justify-end gap-2">
                <button type="button" class="btn btn-secondary" data-dialog-close>{{ __('Schließen') }}</button>
                <a href="#" class="btn btn-primary" data-field="link">{{ __('Öffnen') }}<x-icon name="chevron-right"/></a>
            </div>
        </div>
    </dialog>
@endsection
