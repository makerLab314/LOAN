@extends('layouts.app')
@section('title', __('Vormerken'))

@section('content')
    <x-page-header :title="__('Vormerken: :title', ['title' => $device->title])" :back="route('devices.show', $device)">
        <x-slot:subtitle><span class="id-chip">{{ $device->inventory_number }}</span> · {{ trans_choice(':count Exemplar|:count Exemplare', $device->total_quantity) }}</x-slot:subtitle>
    </x-page-header>

    <div class="grid gap-6 lg:grid-cols-3">
        <form method="POST" action="{{ route('reservations.store', $device) }}" class="card card-body space-y-4 lg:col-span-2">
            @csrf
            <div class="grid gap-4 sm:grid-cols-2">
                <x-field :label="__('Beginn')" name="start_date">
                    <div class="flex gap-2">
                        <input type="date" name="start_date" class="input" value="{{ old('start_date', today()->addDay()->toDateString()) }}" min="{{ today()->toDateString() }}" required>
                        <input type="time" name="start_time" class="input w-28" value="{{ old('start_time', '08:00') }}" required aria-label="{{ __('Startzeit') }}">
                    </div>
                </x-field>
                <x-field :label="__('Ende')" name="end_date">
                    <div class="flex gap-2">
                        <input type="date" name="end_date" class="input" value="{{ old('end_date', today()->addDay()->toDateString()) }}" min="{{ today()->toDateString() }}" required>
                        <input type="time" name="end_time" class="input w-28" value="{{ old('end_time', '16:00') }}" required aria-label="{{ __('Endzeit') }}">
                    </div>
                    @error('end_time')<p class="error">{{ $message }}</p>@enderror
                </x-field>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <x-field :label="__('Menge')" name="quantity">
                    <input type="number" name="quantity" class="input" min="1" max="{{ $device->total_quantity }}" value="{{ old('quantity', 1) }}" required>
                </x-field>
                <x-field :label="__('Für (optional)')" name="reserved_by_name" :hint="__('Wenn nicht für dich selbst.')">
                    <input name="reserved_by_name" class="input" value="{{ old('reserved_by_name') }}" maxlength="255">
                </x-field>
            </div>

            <x-field :label="__('Zweck (optional)')" name="purpose">
                <input name="purpose" class="input" value="{{ old('purpose') }}" maxlength="255">
            </x-field>

            <div class="flex justify-end gap-2 pt-2">
                <a href="{{ route('devices.show', $device) }}" class="btn btn-secondary">{{ __('Abbrechen') }}</a>
                <button class="btn btn-primary"><x-icon name="bookmark"/>{{ __('Vormerken') }}</button>
            </div>
        </form>

        <section class="card h-fit">
            <div class="card-header"><h2 class="card-title">{{ __('Bereits vorgemerkt') }}</h2></div>
            @if ($upcoming->isEmpty())
                <x-empty icon="calendar" :title="__('Noch frei')"/>
            @else
                <ul class="divide-y divide-line text-sm">
                    @foreach ($upcoming as $r)
                        <li class="px-4 py-2.5 sm:px-5">
                            <p class="font-medium">{{ $r->quantity }}× · {{ $r->displayName() }}</p>
                            <p class="text-xs text-muted">{{ $r->start_at->format('d.m. H:i') }} – {{ $r->end_at->format('d.m.Y H:i') }}</p>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    </div>
@endsection
