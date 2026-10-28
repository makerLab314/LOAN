@php
    $items = Gate::allows('devices.view') ? [
        ['route' => 'home', 'match' => 'home', 'icon' => 'home', 'label' => __('Start')],
        ['route' => 'devices.index', 'match' => 'devices.*', 'icon' => 'devices', 'label' => __('Geräte')],
        ['route' => 'scan', 'match' => 'scan', 'icon' => 'scan', 'label' => __('Scannen'), 'primary' => true],
        ['route' => 'loans.index', 'match' => 'loans.*', 'icon' => 'loans', 'label' => __('Ausleihen')],
        ['route' => 'calendar', 'match' => 'calendar', 'icon' => 'calendar', 'label' => __('Kalender')],
    ] : [
        // Raumplanung: Buchen statt Scannen in der Mitte
        ['route' => 'home', 'match' => 'home', 'icon' => 'home', 'label' => __('Start')],
        ['route' => 'rooms.index', 'match' => 'rooms.*', 'icon' => 'door', 'label' => __('Räume')],
        ['route' => 'bookings.new', 'match' => 'bookings.create', 'icon' => 'calendar-plus', 'label' => __('Buchen'), 'primary' => true],
        ['route' => 'bookings.index', 'match' => ['bookings.index', 'bookings.archived', 'bookings.edit'], 'icon' => 'bookmark', 'label' => __('Buchungen')],
        ['route' => 'calendar', 'match' => 'calendar', 'icon' => 'calendar', 'label' => __('Kalender')],
    ];
@endphp
<nav class="glass no-print fixed inset-x-0 bottom-0 z-30 border-t border-[var(--glass-border)] lg:hidden"
     style="padding-bottom: env(safe-area-inset-bottom)" aria-label="{{ __('Schnellnavigation') }}">
    <div class="mx-auto grid max-w-md grid-cols-5">
        @foreach ($items as $item)
            @php $active = request()->routeIs($item['match']); @endphp
            <a href="{{ route($item['route']) }}"
               @class(['flex flex-col items-center gap-0.5 py-2 text-[11px] font-medium', 'text-accent' => $active, 'text-muted' => ! $active])>
                @if (! empty($item['primary']))
                    <span class="hero-tile -mt-5 grid size-12 place-items-center rounded-full ring-4 ring-bg">
                        <x-icon :name="$item['icon']" class="size-5"/>
                    </span>
                @else
                    <x-icon :name="$item['icon']" class="size-5"/>
                @endif
                {{ $item['label'] }}
            </a>
        @endforeach
    </div>
</nav>
