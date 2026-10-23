@extends('layouts.app')
@section('title', __('Kalender'))

@section('content')
    <x-page-header :title="__('Kalender')"/>

    <div class="card mb-4 flex flex-col gap-3 p-3 sm:flex-row sm:items-center" data-calendar-filters>
        <div class="flex flex-wrap gap-2">
            <label class="badge cursor-pointer border border-line bg-surface px-3 py-1.5 text-sm has-checked:border-transparent has-checked:bg-blue-600 has-checked:text-white">
                <input type="checkbox" name="types[]" value="rooms" class="sr-only" checked>{{ __('Raumbuchungen') }}
            </label>
            <label class="badge cursor-pointer border border-line bg-surface px-3 py-1.5 text-sm has-checked:border-transparent has-checked:bg-violet-600 has-checked:text-white">
                <input type="checkbox" name="types[]" value="reservations" class="sr-only" checked>{{ __('Vormerkungen') }}
            </label>
            <label class="badge cursor-pointer border border-line bg-surface px-3 py-1.5 text-sm has-checked:border-transparent has-checked:bg-accent has-checked:text-accent-fg">
                <input type="checkbox" name="types[]" value="loans" class="sr-only" checked>{{ __('Ausleihen') }}
            </label>
        </div>
        <select name="room" class="input sm:ml-auto sm:w-56" aria-label="{{ __('Raum') }}">
            <option value="">{{ __('Alle Räume') }}</option>
            @foreach ($rooms as $room)
                <option value="{{ $room->id }}">{{ $room->name }}</option>
            @endforeach
        </select>
    </div>

    <div class="card p-2 sm:p-4">
        <div data-calendar data-events="{{ route('calendar.events') }}"></div>
    </div>

    <dialog id="event-dialog" class="modal max-w-sm">
        <div class="p-5">
            <p class="text-xs font-medium tracking-wide text-muted uppercase" data-field="kind"></p>
            <h2 class="mt-1 font-semibold" data-field="title"></h2>
            <p class="mt-2 text-sm" data-field="time"></p>
            <p class="mt-1 text-sm text-muted" data-field="detail"></p>
            <div class="mt-5 flex justify-end gap-2">
                <button type="button" class="btn btn-secondary" data-dialog-close>{{ __('Schließen') }}</button>
                <a href="#" class="btn btn-primary" data-field="link">{{ __('Öffnen') }}</a>
            </div>
        </div>
    </dialog>
@endsection
