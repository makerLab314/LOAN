{{-- Eine Raumbuchung als Listenzeile: Zeit, Saal in seiner Farbe, Anlass, Ansprechperson --}}
<li>
    <a href="{{ auth()->user()->can('update', $b) ? route('bookings.edit', $b) : route('bookings.index', ['room' => $b->room_id]) }}"
       class="flex items-start gap-3 px-4 py-3 transition hover:bg-surface-2 sm:px-5">
        <span class="w-24 shrink-0 pt-0.5 text-sm font-medium tabular-nums">{{ $b->timeRange() }}</span>
        <span class="min-w-0 flex-1">
            <span class="flex items-center gap-2 text-sm font-medium">
                <span class="cal-dot cal-dot-filled {{ $b->room?->colorClass() }}" aria-hidden="true"></span>
                <span class="truncate">{{ $b->room?->name }}</span>
            </span>
            @if ($b->purpose)<span class="block truncate text-sm">{{ $b->purpose }}</span>@endif
            <span class="block truncate text-xs text-muted">{{ $b->displayName() }}</span>
        </span>
    </a>
</li>
