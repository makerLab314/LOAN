@php
    // Raumplanung: Geräte, Ausleihen und Scanner gar nicht erst zeigen
    $devices = Gate::allows('devices.view');
    $main = array_filter([
        ['route' => 'home', 'match' => 'home', 'icon' => 'home', 'label' => __('Übersicht')],
        $devices ? ['route' => 'devices.index', 'match' => 'devices.*', 'icon' => 'devices', 'label' => __('Geräte')] : null,
        $devices ? ['route' => 'loans.index', 'match' => 'loans.*', 'icon' => 'loans', 'label' => __('Ausleihen')] : null,
        ['route' => 'calendar', 'match' => 'calendar', 'icon' => 'calendar', 'label' => __('Kalender')],
        ['route' => 'rooms.index', 'match' => 'rooms.*', 'icon' => 'door', 'label' => __('Räume')],
        ['route' => 'bookings.index', 'match' => 'bookings.*', 'icon' => 'bookmark', 'label' => __('Raumbuchungen')],
        $devices ? ['route' => 'scan', 'match' => 'scan', 'icon' => 'scan', 'label' => __('Scannen / ID')] : null,
    ]);
    $admin = array_filter([
        Gate::allows('categories.manage') ? ['route' => 'categories.index', 'match' => 'categories.*', 'icon' => 'tag', 'label' => __('Kategorien')] : null,
        Gate::allows('labels.print') ? ['route' => 'labels.index', 'match' => 'labels.*', 'icon' => 'qr', 'label' => __('Etiketten')] : null,
        Gate::allows('users.manage') ? ['route' => 'admin.users.index', 'match' => 'admin.users.*', 'icon' => 'users', 'label' => __('Nutzer')] : null,
        Gate::allows('audit.view') ? ['route' => 'admin.audit', 'match' => 'admin.audit*', 'icon' => 'shield', 'label' => __('Protokoll')] : null,
        Gate::allows('settings.manage') ? ['route' => 'admin.settings', 'match' => 'admin.settings*', 'icon' => 'settings', 'label' => __('Einstellungen')] : null,
    ]);
@endphp

<div class="flex h-14 shrink-0 items-center gap-2.5 border-b border-line px-5 font-semibold">
    <a href="{{ route('home') }}" class="flex min-w-0 items-center gap-2.5">
        @include('layouts.partials.logo')
    </a>
</div>

<nav class="flex-1 space-y-6 overflow-y-auto px-3 py-4" aria-label="{{ __('Hauptnavigation') }}">
    <div class="space-y-0.5">
        @foreach ($main as $item)
            <a href="{{ route($item['route']) }}" @class(['nav-link', 'active' => request()->routeIs($item['match'])])
               @if (request()->routeIs($item['match'])) aria-current="page" @endif>
                <x-icon :name="$item['icon']"/>{{ $item['label'] }}
            </a>
        @endforeach
    </div>

    @if ($admin)
        <div>
            <p class="px-3 pb-1.5 text-[11px] font-semibold tracking-wider text-muted uppercase">{{ __('Verwaltung') }}</p>
            <div class="space-y-0.5">
                @foreach ($admin as $item)
                    <a href="{{ route($item['route']) }}" @class(['nav-link', 'active' => request()->routeIs($item['match'])])>
                        <x-icon :name="$item['icon']"/>{{ $item['label'] }}
                    </a>
                @endforeach
            </div>
        </div>
    @endif
</nav>

@if ($branding->tagline())
    <p class="border-t border-line px-5 py-3 text-xs text-muted">{{ $branding->tagline() }}</p>
@endif
