@extends('layouts.app')
@section('title', $category->exists ? __('Kategorie bearbeiten') : __('Neue Kategorie'))

@section('content')
    <x-page-header :title="$category->exists ? __('Kategorie bearbeiten') : __('Neue Kategorie')" :back="route('categories.index')"/>

    <form method="POST" action="{{ $category->exists ? route('categories.update', $category) : route('categories.store') }}" class="card card-body max-w-2xl space-y-4">
        @csrf
        @if ($category->exists) @method('PUT') @endif

        <div class="grid gap-4 sm:grid-cols-3">
            <x-field :label="__('Name')" name="name" class="sm:col-span-2">
                <input id="name" name="name" class="input" value="{{ old('name', $category->name) }}" required maxlength="255" autofocus>
            </x-field>
            <x-field :label="__('Kürzel')" name="code" :hint="__('2–5 Zeichen, leer = automatisch')">
                <input id="code" name="code" class="input font-mono uppercase" value="{{ old('code', $category->code) }}" minlength="2" maxlength="5" pattern="[A-Za-z0-9]+" autocomplete="off">
            </x-field>
        </div>
        @if ($category->exists)
            <p class="hint">{{ __('Ein geändertes Kürzel gilt nur für neue Geräte – bestehende Inventar-IDs bleiben, damit gedruckte Etiketten gültig bleiben.') }}</p>
        @endif
        <x-field :label="__('Beschreibung')" name="description">
            <textarea id="description" name="description" rows="3" class="input" maxlength="2000">{{ old('description', $category->description) }}</textarea>
        </x-field>
        <div class="flex justify-end gap-2">
            <a href="{{ route('categories.index') }}" class="btn btn-secondary">{{ __('Abbrechen') }}</a>
            <button class="btn btn-primary"><x-icon name="check"/>{{ __('Speichern') }}</button>
        </div>
    </form>
@endsection
