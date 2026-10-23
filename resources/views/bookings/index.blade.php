@extends('layouts.app')
@section('title', $archived ? __('Vergangene Buchungen') : __('Raumbuchungen'))

@section('content')
    <x-page-header :title="$archived ? __('Vergangene Buchungen') : __('Raumbuchungen')">
        <x-slot:actions>
            @if ($archived)
                <a href="{{ route('bookings.index', array_filter(['room' => $roomId])) }}" class="btn btn-secondary">{{ __('Anstehende') }}</a>
            @else
                <a href="{{ route('bookings.archived', array_filter(['room' => $roomId])) }}" class="btn btn-secondary"><x-icon name="history"/>{{ __('Archiv') }}</a>
            @endif
            <a href="{{ route('calendar') }}" class="btn btn-secondary"><x-icon name="calendar"/>{{ __('Kalender') }}</a>
        </x-slot:actions>
    </x-page-header>

    <div class="mb-4 flex gap-1 overflow-x-auto rounded-xl border border-line bg-surface p-1">
        <a href="{{ request()->fullUrlWithQuery(['room' => null, 'page' => null]) }}" @class(['btn btn-sm', 'bg-accent-soft text-accent' => ! $roomId, 'text-muted' => $roomId])>{{ __('Alle Räume') }}</a>
        @foreach ($rooms as $room)
            <a href="{{ request()->fullUrlWithQuery(['room' => $room->id, 'page' => null]) }}" @class(['btn btn-sm', 'bg-accent-soft text-accent' => $roomId === $room->id, 'text-muted' => $roomId !== $room->id])>{{ $room->name }}</a>
        @endforeach
    </div>

    <div class="card overflow-hidden">
        @if ($bookings->isEmpty())
            <x-empty icon="door" :title="__('Keine Buchungen')"/>
        @else
            <ul class="divide-y divide-line">
                @foreach ($bookings as $b)
                    <li class="flex flex-col gap-3 px-4 py-3 sm:flex-row sm:items-center sm:px-5">
                        <div class="w-20 shrink-0">
                            <p class="text-xs text-muted uppercase">{{ $b->start_at->translatedFormat('D') }}</p>
                            <p class="font-semibold">{{ $b->start_at->format('d.m.y') }}</p>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="font-medium">{{ $b->room?->name }} <span class="font-normal text-muted">· {{ $b->start_at->format('H:i') }} – {{ $b->end_at->format($b->end_at->isSameDay($b->start_at) ? 'H:i' : 'd.m. H:i') }}</span></p>
                            <p class="text-sm text-muted">{{ $b->purpose }}</p>
                            <p class="text-xs text-muted">{{ $b->user?->name }}</p>
                        </div>
                        @can('update', $b)
                            <div class="flex gap-2">
                                @unless ($archived)
                                    <a href="{{ route('bookings.edit', $b) }}" class="btn btn-secondary btn-sm"><x-icon name="edit"/>{{ __('Ändern') }}</a>
                                @endunless
                                <form method="POST" action="{{ route('bookings.destroy', $b) }}" data-confirm="{{ __('Buchung stornieren?') }}">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-danger-ghost btn-sm">{{ $archived ? __('Löschen') : __('Stornieren') }}</button>
                                </form>
                            </div>
                        @endcan
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
    <div class="mt-4">{{ $bookings->links() }}</div>
@endsection
