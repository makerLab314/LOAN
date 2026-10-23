@extends('layouts.app')
@section('title', __('Kategorien'))

@section('content')
    <x-page-header :title="__('Kategorien')">
        <x-slot:subtitle>{{ __('Das Kürzel bildet den Anfang der Inventar-IDs, z. B. KAB-0042.') }}</x-slot:subtitle>
        <x-slot:actions>
            <a href="{{ route('categories.create') }}" class="btn btn-primary"><x-icon name="plus"/>{{ __('Neue Kategorie') }}</a>
        </x-slot:actions>
    </x-page-header>

    <div class="card overflow-x-auto">
        @if ($categories->isEmpty())
            <x-empty icon="tag" :title="__('Noch keine Kategorien')"/>
        @else
            <table class="table">
                <thead>
                <tr>
                    <th>{{ __('Kürzel') }}</th>
                    <th>{{ __('Name') }}</th>
                    <th class="hidden md:table-cell">{{ __('Beschreibung') }}</th>
                    <th class="text-right">{{ __('Geräte') }}</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @foreach ($categories as $category)
                    <tr>
                        <td><span class="id-chip">{{ $category->code }}</span></td>
                        <td class="font-medium">{{ $category->name }}</td>
                        <td class="hidden max-w-sm truncate text-muted md:table-cell">{{ $category->description }}</td>
                        <td class="text-right tabular-nums">
                            <a href="{{ route('devices.index', ['category' => $category->id]) }}" class="hover:text-accent">{{ $category->devices_count }}</a>
                        </td>
                        <td class="text-right whitespace-nowrap">
                            <a href="{{ route('categories.edit', $category) }}" class="btn btn-ghost btn-sm" aria-label="{{ __('Bearbeiten') }}"><x-icon name="edit"/></a>
                            @if ($category->devices_count === 0)
                                <form method="POST" action="{{ route('categories.destroy', $category) }}" class="inline" data-confirm="{{ __('Kategorie löschen?') }}">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-danger-ghost btn-sm" aria-label="{{ __('Löschen') }}"><x-icon name="trash"/></button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endif
    </div>
@endsection
