@extends('layouts.app')
@section('title', __('Kalender'))

@php
    $types = [
        ['value' => 'rooms', 'class' => 'ev-room', 'label' => __('Raumbuchungen')],
        ['value' => 'reservations', 'class' => 'ev-reservation', 'label' => __('Vormerkungen')],
        ['value' => 'loans', 'class' => 'ev-loan', 'label' => __('Ausleihen')],
    ];
@endphp

@section('content')
    <x-page-header :title="__('Kalender')">
        <x-slot:subtitle>{{ __('Räume, Vormerkungen und Ausleihen auf einen Blick.') }}</x-slot:subtitle>
    </x-page-header>

    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center" data-calendar-filters>
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
        <select name="room" class="input sm:ml-auto sm:w-56" aria-label="{{ __('Raum') }}">
            <option value="">{{ __('Alle Räume') }}</option>
            @foreach ($rooms as $room)
                <option value="{{ $room->id }}">{{ $room->name }}</option>
            @endforeach
        </select>
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
