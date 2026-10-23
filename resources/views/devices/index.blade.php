@extends('layouts.app')
@section('title', __('Geräte'))

@section('content')
<div x-data="deviceFilter" data-category="{{ request('category') }}">
    <x-page-header :title="__('Geräte')">
        <x-slot:subtitle>
            <span x-text="filtered ? shown + ' / ' + total : total"></span> {{ __('Geräte') }}
        </x-slot:subtitle>
        <x-slot:actions>
            @can('labels.print')
                <a href="{{ route('labels.index') }}" class="btn btn-secondary"><x-icon name="printer"/><span class="hidden sm:inline">{{ __('Etiketten') }}</span></a>
            @endcan
            @can('devices.manage')
                <a href="{{ route('devices.create') }}" class="btn btn-primary"><x-icon name="plus"/>{{ __('Neues Gerät') }}</a>
            @endcan
        </x-slot:actions>
    </x-page-header>

    {{-- Filter --}}
    <div class="card mb-5 flex flex-col gap-3 p-3 md:flex-row md:items-center">
        <div class="relative flex-1">
            <x-icon name="search" class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted"/>
            <input x-model.debounce.150ms="q" type="search" class="input pl-9" placeholder="{{ __('Name, ID oder Beschreibung…') }}" aria-label="{{ __('Geräte durchsuchen') }}">
        </div>
        <div class="grid grid-cols-2 gap-3 md:flex">
            <select x-model="category" class="input md:w-48" aria-label="{{ __('Kategorie') }}">
                <option value="">{{ __('Alle Kategorien') }}</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                @endforeach
            </select>
            <select x-model="status" class="input md:w-44" aria-label="{{ __('Status') }}">
                <option value="">{{ __('Jeder Status') }}</option>
                <option value="free">{{ __('Verfügbar') }}</option>
                <option value="loaned">{{ __('Verliehen') }}</option>
                <option value="reserved">{{ __('Vorgemerkt') }}</option>
            </select>
        </div>
        <div class="hidden items-center gap-1 rounded-lg border border-line p-0.5 md:flex">
            <button type="button" class="btn btn-sm" :class="isGrid ? 'bg-surface-2 text-fg' : 'text-muted'" data-view="grid" @click="setView" aria-label="{{ __('Kacheln') }}"><x-icon name="grid"/></button>
            <button type="button" class="btn btn-sm" :class="isList ? 'bg-surface-2 text-fg' : 'text-muted'" data-view="list" @click="setView" aria-label="{{ __('Liste') }}"><x-icon name="list"/></button>
        </div>
    </div>

    @if ($devices->isEmpty())
        <div class="card">
            <x-empty icon="devices" :title="__('Noch keine Geräte')">
                @can('devices.manage') {{ __('Lege das erste Gerät an.') }} @endcan
            </x-empty>
        </div>
    @else
        {{-- Kacheln --}}
        <div x-show="isGrid" class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4">
            @foreach ($devices as $device)
                @php
                    $statuses = trim(($device->available_quantity > 0 ? 'free ' : '').($device->loaned_quantity > 0 ? 'loaned ' : '').($device->upcoming_reservations_count > 0 ? 'reserved' : ''));
                    $search = mb_strtolower($device->title.' '.$device->inventory_number.' '.$device->description.' '.$device->category?->name);
                @endphp
                <a href="{{ route('devices.show', $device) }}" data-item data-count data-search="{{ $search }}" data-category="{{ $device->category_id }}" data-status="{{ $statuses }}"
                   class="card group flex overflow-hidden transition hover:border-accent/40 hover:shadow-md">
                    <div class="aspect-square w-28 shrink-0 overflow-hidden bg-surface-2 sm:w-32">
                        @if ($device->thumb_url)
                            <img src="{{ $device->thumb_url }}" alt="" loading="lazy" class="size-full object-cover transition group-hover:scale-105">
                        @else
                            <div class="grid size-full place-items-center text-muted/60"><x-icon name="image" class="size-8"/></div>
                        @endif
                    </div>
                    <div class="flex min-w-0 flex-1 flex-col gap-1.5 p-3.5">
                        <div class="flex items-start justify-between gap-2">
                            <span class="id-chip">{{ $device->inventory_number }}</span>
                            @if ($device->upcoming_reservations_count)
                                <span class="badge badge-info" title="{{ __('Vormerkungen') }}"><x-icon name="bookmark" class="size-3"/>{{ $device->upcoming_reservations_count }}</span>
                            @endif
                        </div>
                        <p class="line-clamp-2 leading-snug font-medium">{{ $device->title }}</p>
                        <p class="truncate text-xs text-muted">{{ $device->category?->name }}</p>
                        <div class="mt-auto pt-1"><x-device-status :device="$device"/></div>
                    </div>
                </a>
            @endforeach
        </div>

        {{-- Liste --}}
        <div x-show="isList" x-cloak class="card overflow-x-auto">
            <table class="table">
                <thead>
                <tr>
                    <th>{{ __('ID') }}</th>
                    <th>{{ __('Gerät') }}</th>
                    <th class="hidden md:table-cell">{{ __('Kategorie') }}</th>
                    <th>{{ __('Status') }}</th>
                </tr>
                </thead>
                <tbody>
                @foreach ($devices as $device)
                    @php
                        $statuses = trim(($device->available_quantity > 0 ? 'free ' : '').($device->loaned_quantity > 0 ? 'loaned ' : '').($device->upcoming_reservations_count > 0 ? 'reserved' : ''));
                        $search = mb_strtolower($device->title.' '.$device->inventory_number.' '.$device->description.' '.$device->category?->name);
                    @endphp
                    <tr data-item data-search="{{ $search }}" data-category="{{ $device->category_id }}" data-status="{{ $statuses }}">
                        <td><span class="id-chip">{{ $device->inventory_number }}</span></td>
                        <td><a href="{{ route('devices.show', $device) }}" class="font-medium hover:text-accent">{{ $device->title }}</a></td>
                        <td class="hidden text-muted md:table-cell">{{ $device->category?->name }}</td>
                        <td><x-device-status :device="$device"/></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>

        <div x-show="shown === 0" x-cloak class="card mt-4">
            <x-empty icon="search" :title="__('Keine passenden Geräte')">
                <button type="button" class="btn btn-secondary btn-sm mt-3" @click="reset">{{ __('Filter zurücksetzen') }}</button>
            </x-empty>
        </div>
    @endif
</div>
@endsection
