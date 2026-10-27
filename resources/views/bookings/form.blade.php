@extends('layouts.app')
@section('title', $booking->exists ? __('Buchung ändern') : __('Raum buchen'))

@php
    $start = $booking->start_at ?? ($date ? \Illuminate\Support\Carbon::parse($date)->setTime(8, 0) : now()->addHour()->startOfHour());
    $end = $booking->end_at ?? $start->copy()->addHours(2);
    $roomId = (int) old('room_id', $booking->room_id ?? $room->id);
    $kiosk = auth()->user()->isKiosk();
    $presets = ['45' => __('45 Min.'), '60' => __('1 Std.'), '90' => __('1,5 Std.'), '120' => __('2 Std.'), 'day' => __('ganztägig')];
@endphp

@section('content')
    <x-page-header :title="$booking->exists ? __('Buchung ändern') : __('Raum buchen')" :back="route('bookings.index')"/>

    <div class="grid gap-6 lg:grid-cols-5" x-data="bookingForm" data-availability="{{ route('bookings.availability') }}" data-ignore="{{ $booking->id }}">
        <form method="POST" action="{{ $booking->exists ? route('bookings.update', $booking) : route('bookings.store', $room) }}" class="card card-body space-y-5 lg:col-span-3">
            @csrf
            @if ($booking->exists) @method('PUT') @endif

            <fieldset>
                <legend class="label">{{ __('Raum') }}</legend>
                <div @class(['grid gap-2', 'sm:grid-cols-2' => $rooms->count() === 2, 'sm:grid-cols-3' => $rooms->count() >= 3])>
                    @foreach ($rooms as $r)
                        <label class="flex cursor-pointer flex-col rounded-xl border border-line bg-surface px-3 py-2.5 transition has-checked:border-accent has-checked:bg-accent-soft has-focus-visible:ring-2 has-focus-visible:ring-accent">
                            <input type="radio" name="room_id" value="{{ $r->id }}" class="sr-only" data-name="{{ $r->name }}" @checked($roomId === $r->id) @change="check">
                            <span class="font-medium">{{ $r->name }}</span>
                            <span class="text-xs text-muted">{{ $r->location }}</span>
                        </label>
                    @endforeach
                </div>
                @error('room_id')<p class="error">{{ $message }}</p>@enderror
            </fieldset>

            <div class="grid gap-4 sm:grid-cols-2">
                <x-field :label="__('Beginn')" name="start_date">
                    <div class="flex gap-2">
                        <input type="date" name="start_date" class="input" value="{{ old('start_date', $start->toDateString()) }}" required @change="startChanged">
                        <input type="time" name="start_time" class="input w-28" value="{{ old('start_time', $start->format('H:i')) }}" required aria-label="{{ __('Startzeit') }}" @change="startChanged">
                    </div>
                </x-field>
                <x-field :label="__('Ende')" name="end_date">
                    <div class="flex gap-2">
                        <input type="date" name="end_date" class="input" value="{{ old('end_date', $end->toDateString()) }}" required @change="endChanged">
                        <input type="time" name="end_time" class="input w-28" value="{{ old('end_time', $end->format('H:i')) }}" required aria-label="{{ __('Endzeit') }}" @change="endChanged">
                    </div>
                    @error('end_time')<p class="error">{{ $message }}</p>@enderror
                </x-field>
            </div>

            <div class="flex flex-wrap items-center gap-1.5" role="group" aria-label="{{ __('Dauer') }}">
                <span class="mr-1 text-sm text-muted">{{ __('Dauer') }}</span>
                @foreach ($presets as $value => $label)
                    <button type="button" class="btn btn-sm border" data-preset="{{ $value }}" @click="preset"
                            :class="isPreset('{{ $value }}') ? 'border-accent bg-accent-soft text-accent' : 'border-line text-muted'"
                            :aria-pressed="isPreset('{{ $value }}')">{{ $label }}</button>
                @endforeach
            </div>

            {{-- Live-Prüfung: frei, Überschneidung oder Hinweis --}}
            <div aria-live="polite" class="space-y-2">
                <div x-show="problem" x-cloak class="flex items-start gap-2 rounded-xl border border-warn/40 bg-warn/10 px-3 py-2.5 text-sm">
                    <x-icon name="alert" class="mt-0.5 size-4 shrink-0 text-warn"/>
                    <p x-text="problem"></p>
                </div>
                <div x-show="isFree" x-cloak class="flex items-center gap-2 rounded-xl border border-ok/40 bg-ok/10 px-3 py-2.5 text-sm">
                    <x-icon name="check" class="size-4 shrink-0 text-ok"/>
                    <p><span class="font-medium" x-text="roomName"></span> {{ __('ist in diesem Zeitraum frei.') }}</p>
                </div>
                <div x-show="hasConflict" x-cloak class="rounded-xl border border-danger/40 bg-danger/10 px-3 py-2.5 text-sm">
                    <p class="flex items-center gap-2 font-medium">
                        <x-icon name="x" class="size-4 shrink-0 text-danger"/>
                        <span><span x-text="roomName"></span> {{ __('ist in diesem Zeitraum schon belegt:') }}</span>
                    </p>
                    <ul class="mt-1.5 space-y-0.5 pl-6">
                        <template x-for="(c, index) in conflicts" :key="index">
                            <li><span class="font-medium" x-text="c.date + ', ' + c.time"></span> <span class="text-muted" x-text="c.label"></span></li>
                        </template>
                    </ul>
                    <div x-show="free.length > 0" class="mt-2.5 flex flex-wrap items-center gap-2 border-t border-danger/20 pt-2.5 pl-6">
                        <span class="text-muted">{{ __('Zur gleichen Zeit frei:') }}</span>
                        <template x-for="f in free" :key="f.id">
                            <button type="button" class="btn btn-secondary btn-sm" :data-room="f.id" @click="switchRoom">
                                <span x-text="f.name"></span> {{ __('nehmen') }}
                            </button>
                        </template>
                    </div>
                    <p x-show="free.length === 0" class="mt-1.5 pl-6 text-muted">{{ __('Kein anderer Saal ist in diesem Zeitraum frei.') }}</p>
                </div>
            </div>

            <x-field :label="__('Wofür / für wen')" name="purpose">
                <input id="purpose" name="purpose" class="input" value="{{ old('purpose', $booking->purpose) }}" required maxlength="255" placeholder="{{ __('z. B. Elternabend 7b, Abiprüfung Deutsch') }}">
            </x-field>

            @if ($kiosk)
                <x-field :label="__('Dein Name')" name="booked_by_name">
                    <input id="booked_by_name" name="booked_by_name" class="input" value="{{ old('booked_by_name') }}" required maxlength="255" autocomplete="off" placeholder="{{ __('Name, Klasse oder Gruppe') }}">
                </x-field>
            @else
                <x-field :label="__('Ansprechperson (optional)')" name="booked_by_name" :hint="__('Wer vor Ort zuständig ist. Leer lassen, wenn du das selbst bist.')">
                    <input id="booked_by_name" name="booked_by_name" class="input" value="{{ old('booked_by_name', $booking->booked_by_name) }}" maxlength="255" autocomplete="off" placeholder="{{ auth()->user()->name }}">
                </x-field>
            @endif

            <div class="flex justify-end gap-2 pt-2">
                <a href="{{ route('bookings.index') }}" class="btn btn-secondary">{{ __('Abbrechen') }}</a>
                <button class="btn btn-primary" :disabled="hasConflict"><x-icon name="check"/>{{ $booking->exists ? __('Speichern') : __('Buchen') }}</button>
            </div>
        </form>

        {{-- Belegung aller Säle am gewählten Tag --}}
        <section class="card h-fit lg:col-span-2">
            <div class="card-header">
                <h2 class="card-title">{{ __('Belegung') }} <span class="font-normal text-muted" x-text="dayLabel"></span></h2>
            </div>
            <div class="space-y-4 p-4 sm:p-5">
                <div class="relative ml-20 h-4 text-[10px] text-muted" aria-hidden="true">
                    <template x-for="tick in ticks" :key="tick.label">
                        <span class="absolute -translate-x-1/2" :style="tick.style" x-text="tick.label"></span>
                    </template>
                </div>
                <template x-for="row in rows" :key="row.id">
                    <div>
                        <div class="flex items-center gap-2">
                            <p class="w-18 shrink-0 truncate text-sm" :class="row.selected ? 'font-semibold' : 'text-muted'" x-text="row.name"></p>
                            <div class="relative h-7 flex-1 overflow-hidden rounded-md bg-surface-2">
                                <template x-for="(bar, index) in row.bars" :key="index">
                                    <div class="absolute inset-y-0 rounded-sm bg-blue-600/70" :style="bar.style" :title="bar.title"></div>
                                </template>
                                <div x-show="row.selected && hasSelection" class="absolute inset-y-0 rounded-sm border-2" :class="hasConflict ? 'border-danger bg-danger/30' : 'border-accent bg-accent/25'" :style="selectionStyle"></div>
                            </div>
                        </div>
                        <ul class="mt-1 ml-20 space-y-0.5 text-xs text-muted">
                            <template x-for="(bar, index) in row.bars" :key="index">
                                <li><span class="tabular-nums" x-text="bar.time"></span> · <span x-text="bar.label"></span></li>
                            </template>
                            <li x-show="row.bars.length === 0">{{ __('frei') }}</li>
                        </ul>
                    </div>
                </template>
                <p x-show="rows.length === 0" class="text-sm text-muted">{{ __('Wird geladen …') }}</p>
            </div>
        </section>
    </div>
@endsection
