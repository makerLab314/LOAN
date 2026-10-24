@extends('layouts.app')
@section('title', $booking->exists ? __('Buchung ändern') : __('Raum buchen'))

@php
    $start = $booking->start_at ?? ($date ? \Illuminate\Support\Carbon::parse($date)->setTime(8, 0) : now()->addHour()->startOfHour());
    $end = $booking->end_at ?? $start->copy()->addHours(2);
@endphp

@section('content')
    <x-page-header :title="$booking->exists ? __('Buchung ändern') : __(':room buchen', ['room' => $room->name])" :back="route('bookings.index')">
        <x-slot:subtitle>{{ $room->location }}</x-slot:subtitle>
    </x-page-header>

    <div class="grid gap-6 lg:grid-cols-3">
        <form method="POST" action="{{ $booking->exists ? route('bookings.update', $booking) : route('bookings.store', $room) }}" class="card card-body space-y-4 lg:col-span-2">
            @csrf
            @if ($booking->exists)
                @method('PUT')
                <x-field :label="__('Raum')" name="room_id">
                    <select name="room_id" class="input">
                        @foreach ($rooms as $r)
                            <option value="{{ $r->id }}" @selected(old('room_id', $booking->room_id) == $r->id)>{{ $r->name }}</option>
                        @endforeach
                    </select>
                </x-field>
            @endif

            <div class="grid gap-4 sm:grid-cols-2">
                <x-field :label="__('Beginn')" name="start_date">
                    <div class="flex gap-2">
                        <input type="date" name="start_date" class="input" value="{{ old('start_date', $start->toDateString()) }}" required>
                        <input type="time" name="start_time" class="input w-28" value="{{ old('start_time', $start->format('H:i')) }}" required aria-label="{{ __('Startzeit') }}">
                    </div>
                </x-field>
                <x-field :label="__('Ende')" name="end_date">
                    <div class="flex gap-2">
                        <input type="date" name="end_date" class="input" value="{{ old('end_date', $end->toDateString()) }}" required>
                        <input type="time" name="end_time" class="input w-28" value="{{ old('end_time', $end->format('H:i')) }}" required aria-label="{{ __('Endzeit') }}">
                    </div>
                    @error('end_time')<p class="error">{{ $message }}</p>@enderror
                </x-field>
            </div>
            @if (! $booking->exists && auth()->user()->isKiosk())
                <x-field :label="__('Dein Name')" name="booked_by_name">
                    <input name="booked_by_name" class="input" value="{{ old('booked_by_name') }}" required maxlength="255" autocomplete="off" placeholder="{{ __('Name, Klasse oder Gruppe') }}">
                </x-field>
            @endif
            <x-field :label="__('Zweck')" name="purpose">
                <input name="purpose" class="input" value="{{ old('purpose', $booking->purpose) }}" required maxlength="255" placeholder="{{ __('z. B. Elternabend 7b') }}">
            </x-field>
            <div class="flex justify-end gap-2 pt-2">
                <a href="{{ route('bookings.index') }}" class="btn btn-secondary">{{ __('Abbrechen') }}</a>
                <button class="btn btn-primary"><x-icon name="check"/>{{ $booking->exists ? __('Speichern') : __('Buchen') }}</button>
            </div>
        </form>

        <section class="card h-fit">
            <div class="card-header"><h2 class="card-title">{{ __('Bereits gebucht') }}</h2></div>
            @if ($upcoming->isEmpty())
                <x-empty icon="calendar" :title="__('Keine weiteren Buchungen')"/>
            @else
                <ul class="divide-y divide-line text-sm">
                    @foreach ($upcoming as $b)
                        <li class="px-4 py-2.5 sm:px-5">
                            <p class="font-medium">{{ $b->start_at->translatedFormat('D d.m.') }}, {{ $b->start_at->format('H:i') }}–{{ $b->end_at->format($b->end_at->isSameDay($b->start_at) ? 'H:i' : 'd.m. H:i') }}</p>
                            <p class="text-xs text-muted">{{ $b->purpose }} · {{ $b->displayName() }}</p>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    </div>
@endsection
