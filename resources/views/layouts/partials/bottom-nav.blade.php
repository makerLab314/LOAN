@php
    $items = [
        ['route' => 'home', 'match' => 'home', 'icon' => 'home', 'label' => __('Start')],
        ['route' => 'devices.index', 'match' => 'devices.*', 'icon' => 'devices', 'label' => __('Geräte')],
        ['route' => 'scan', 'match' => 'scan', 'icon' => 'scan', 'label' => __('Scannen'), 'primary' => true],
        ['route' => 'loans.index', 'match' => 'loans.*', 'icon' => 'loans', 'label' => __('Ausleihen')],
        ['route' => 'calendar', 'match' => 'calendar', 'icon' => 'calendar', 'label' => __('Kalender')],
    ];
@endphp
<nav class="no-print fixed inset-x-0 bottom-0 z-30 border-t border-line bg-surface/95 backdrop-blur lg:hidden"
     style="padding-bottom: env(safe-area-inset-bottom)" aria-label="{{ __('Schnellnavigation') }}">
    <div class="mx-auto grid max-w-md grid-cols-5">
        @foreach ($items as $item)
            @php $active = request()->routeIs($item['match']); @endphp
            <a href="{{ route($item['route']) }}"
               @class(['flex flex-col items-center gap-0.5 py-2 text-[11px] font-medium', 'text-accent' => $active, 'text-muted' => ! $active])>
                @if (! empty($item['primary']))
                    <span class="-mt-5 grid size-12 place-items-center rounded-full bg-accent text-accent-fg shadow-lg ring-4 ring-bg">
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
