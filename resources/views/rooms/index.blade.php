@extends('layouts.app')
@section('title', __('Räume'))

@section('content')
    <x-page-header :title="__('Räume')">
        <x-slot:actions>
            <a href="{{ route('bookings.index') }}" class="btn btn-secondary"><x-icon name="bookmark"/>{{ __('Buchungen') }}</a>
            @can('rooms.manage')
                <a href="{{ route('rooms.create') }}" class="btn btn-primary"><x-icon name="plus"/>{{ __('Neuer Raum') }}</a>
            @endcan
        </x-slot:actions>
    </x-page-header>

    @if ($rooms->isEmpty())
        <div class="card"><x-empty icon="door" :title="__('Noch keine Räume')"/></div>
    @else
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ($rooms as $room)
                @php $current = $room->bookings->first(); @endphp
                <div class="card flex flex-col">
                    <div class="card-body flex-1">
                        <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0">
                                <h2 class="truncate font-semibold">{{ $room->name }}</h2>
                                <p class="text-sm text-muted">{{ $room->location }}</p>
                            </div>
                            @if ($current)
                                <span class="badge badge-danger">{{ __('belegt') }}</span>
                            @else
                                <span class="badge badge-ok">{{ __('frei') }}</span>
                            @endif
                        </div>
                        @if ($room->description)<p class="mt-3 line-clamp-3 text-sm text-muted">{{ $room->description }}</p>@endif
                        @if ($current)
                            <p class="mt-3 text-xs text-muted">{{ __('bis :time · :name', ['time' => $current->end_at->format('H:i'), 'name' => $current->displayName()]) }}</p>
                        @endif
                        <p class="mt-3 text-xs text-muted">{{ trans_choice(':count anstehende Buchung|:count anstehende Buchungen', $room->upcoming_count) }}</p>
                    </div>
                    <div class="flex items-center gap-2 border-t border-line px-4 py-3 sm:px-5">
                        @can('rooms.book')
                            <a href="{{ route('bookings.create', $room) }}" class="btn btn-primary btn-sm flex-1"><x-icon name="calendar-plus"/>{{ __('Buchen') }}</a>
                        @endcan
                        <a href="{{ route('bookings.index', ['room' => $room->id]) }}" class="btn btn-secondary btn-sm">{{ __('Belegung') }}</a>
                        @can('rooms.manage')
                            <a href="{{ route('rooms.edit', $room) }}" class="btn btn-ghost btn-sm btn-icon" aria-label="{{ __('Bearbeiten') }}"><x-icon name="edit"/></a>
                        @endcan
                    </div>
                </div>
            @endforeach
        </div>
    @endif
@endsection
