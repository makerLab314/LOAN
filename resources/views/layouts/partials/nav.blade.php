@php
    // Raumplanung: Geräte, Ausleihen und Scanner gar nicht erst zeigen
    $devices = Gate::allows('devices.view');
    $main = array_filter([
        ['route' => 'home', 'match' => 'home', 'icon' => 'home', 'label' => __('Übersicht'), 'short' => __('Start')],
        $devices ? ['route' => 'devices.index', 'match' => 'devices.*', 'icon' => 'devices', 'label' => __('Geräte')] : null,
        $devices ? ['route' => 'loans.index', 'match' => 'loans.*', 'icon' => 'loans', 'label' => __('Ausleihen')] : null,
        ['route' => 'calendar', 'match' => 'calendar', 'icon' => 'calendar', 'label' => __('Kalender')],
        ['route' => 'rooms.index', 'match' => 'rooms.*', 'icon' => 'door', 'label' => __('Räume')],
        ['route' => 'bookings.index', 'match' => 'bookings.*', 'icon' => 'bookmark', 'label' => __('Raumbuchungen'), 'short' => __('Buchungen')],
        $devices ? ['route' => 'scan', 'match' => 'scan', 'icon' => 'scan', 'label' => __('Scannen / ID'), 'short' => __('Scannen')] : null,
    ]);
    $admin = array_filter([
        Gate::allows('categories.manage') ? ['route' => 'categories.index', 'match' => 'categories.*', 'icon' => 'tag', 'label' => __('Kategorien')] : null,
        Gate::allows('labels.print') ? ['route' => 'labels.index', 'match' => 'labels.*', 'icon' => 'qr', 'label' => __('Etiketten')] : null,
        Gate::allows('users.manage') ? ['route' => 'admin.users.index', 'match' => 'admin.users.*', 'icon' => 'users', 'label' => __('Nutzer')] : null,
        Gate::allows('audit.view') ? ['route' => 'admin.audit', 'match' => 'admin.audit*', 'icon' => 'shield', 'label' => __('Protokoll')] : null,
        Gate::allows('settings.manage') ? ['route' => 'admin.settings', 'match' => 'admin.settings*', 'icon' => 'settings', 'label' => __('Einstellungen'), 'short' => __('Optionen')] : null,
    ]);
@endphp

{{-- lg:max-xl: schmale Symbolleiste mit Kurzbeschriftung (Tablet quer). Der Drawer ist dort ausgeblendet, betrifft also nur die Seitenleiste. --}}
<div class="flex h-14 shrink-0 items-center gap-2.5 border-b border-line px-5 font-semibold lg:max-xl:justify-center lg:max-xl:px-0 lg:max-xl:[&_.brand-name]:hidden">
    <a href="{{ route('home') }}" class="flex min-w-0 items-center gap-2.5" title="{{ $branding->name() }}">
        @include('layouts.partials.logo')
    </a>
</div>

<nav class="flex-1 space-y-6 overflow-y-auto px-3 py-4 lg:max-xl:space-y-3 lg:max-xl:px-2 lg:max-xl:py-3" aria-label="{{ __('Hauptnavigation') }}">
    <div class="space-y-0.5 lg:max-xl:space-y-1">
        @foreach ($main as $item)
            <a href="{{ route($item['route']) }}" @class(['nav-link', 'active' => request()->routeIs($item['match'])]) title="{{ $item['label'] }}"
               @if (request()->routeIs($item['match'])) aria-current="page" @endif>
                <x-icon :name="$item['icon']"/><span class="lg:max-xl:hidden">{{ $item['label'] }}</span><span class="nav-short" aria-hidden="true">{{ $item['short'] ?? $item['label'] }}</span>
            </a>
        @endforeach
    </div>

    @if ($admin)
        <div class="lg:max-xl:border-t lg:max-xl:border-line lg:max-xl:pt-3">
            <p class="px-3 pb-1.5 text-[11px] font-semibold tracking-wider text-muted uppercase lg:max-xl:hidden">{{ __('Verwaltung') }}</p>
            <div class="space-y-0.5 lg:max-xl:space-y-1">
                @foreach ($admin as $item)
                    <a href="{{ route($item['route']) }}" @class(['nav-link', 'active' => request()->routeIs($item['match'])]) title="{{ $item['label'] }}">
                        <x-icon :name="$item['icon']"/><span class="lg:max-xl:hidden">{{ $item['label'] }}</span><span class="nav-short" aria-hidden="true">{{ $item['short'] ?? $item['label'] }}</span>
                    </a>
                @endforeach
            </div>
        </div>
    @endif
</nav>

{{-- Kiosk: Die Kopfleiste fehlt ab Tablet-Breite, Farbschema und Beenden stehen hier --}}
@if ($kiosk ?? false)
    <div class="hidden shrink-0 items-center gap-1 border-t border-line px-3 py-2 lg:flex lg:max-xl:flex-col lg:max-xl:px-2">
        @include('layouts.partials.theme-toggle', ['placement' => 'bottom-0 left-full ml-2'])
        <a href="{{ route('kiosk.exit') }}" class="btn btn-ghost btn-icon" title="{{ __('Kiosk beenden') }}" aria-label="{{ __('Kiosk beenden') }}"><x-icon name="key" class="size-5"/></a>
    </div>
@endif

@if ($branding->tagline())
    <p class="border-t border-line px-5 py-3 text-xs text-muted lg:max-xl:hidden">{{ $branding->tagline() }}</p>
@endif
