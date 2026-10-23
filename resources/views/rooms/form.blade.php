@extends('layouts.app')
@section('title', $room->exists ? __('Raum bearbeiten') : __('Neuer Raum'))

@section('content')
    <x-page-header :title="$room->exists ? __('Raum bearbeiten') : __('Neuer Raum')" :back="route('rooms.index')"/>

    <form method="POST" action="{{ $room->exists ? route('rooms.update', $room) : route('rooms.store') }}" class="card card-body max-w-2xl space-y-4">
        @csrf
        @if ($room->exists) @method('PUT') @endif
        <div class="grid gap-4 sm:grid-cols-2">
            <x-field :label="__('Name')" name="name">
                <input id="name" name="name" class="input" value="{{ old('name', $room->name) }}" required maxlength="255" autofocus>
            </x-field>
            <x-field :label="__('Ort')" name="location">
                <input id="location" name="location" class="input" value="{{ old('location', $room->location) }}" required maxlength="255" placeholder="{{ __('z. B. Gebäude A, 2. OG') }}">
            </x-field>
        </div>
        <x-field :label="__('Beschreibung')" name="description">
            <textarea id="description" name="description" rows="3" class="input" maxlength="2000">{{ old('description', $room->description) }}</textarea>
        </x-field>
        <div class="flex items-center justify-between gap-2">
            <div>
                @if ($room->exists)
                    <button form="delete-room" class="btn btn-danger-ghost btn-sm"><x-icon name="trash"/>{{ __('Löschen') }}</button>
                @endif
            </div>
            <div class="flex gap-2">
                <a href="{{ route('rooms.index') }}" class="btn btn-secondary">{{ __('Abbrechen') }}</a>
                <button class="btn btn-primary"><x-icon name="check"/>{{ __('Speichern') }}</button>
            </div>
        </div>
    </form>
    @if ($room->exists)
        <form id="delete-room" method="POST" action="{{ route('rooms.destroy', $room) }}" data-confirm="{{ __('Raum löschen?') }}">
            @csrf @method('DELETE')
        </form>
    @endif
@endsection
