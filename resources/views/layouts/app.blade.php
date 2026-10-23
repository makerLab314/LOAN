<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full"
      data-theme="{{ auth()->user()?->theme ?? 'system' }}"
      style="--accent: {{ $branding->accent() }}; --accent-fg: {{ $branding->accentForeground() }};">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="{{ $branding->accent() }}">
    <title>@hasSection('title')@yield('title') · @endif{{ $branding->name() }}</title>
    <link rel="icon" href="{{ $branding->faviconUrl() }}">
    @include('layouts.partials.theme-script')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full">
<div x-data="shell" class="min-h-full" @keydown.window="onKey">

    {{-- Sidebar (Desktop) --}}
    <aside class="fixed inset-y-0 left-0 z-30 hidden w-64 flex-col border-r border-line bg-surface lg:flex">
        @include('layouts.partials.nav')
    </aside>

    {{-- Drawer (Mobil) --}}
    <div x-cloak x-show="drawer" class="fixed inset-0 z-40 lg:hidden" role="dialog" aria-modal="true">
        <div class="absolute inset-0 bg-black/40" @click="closeDrawer"></div>
        <aside class="absolute inset-y-0 left-0 flex w-72 max-w-[85%] flex-col bg-surface shadow-2xl">
            @include('layouts.partials.nav')
        </aside>
    </div>

    <div class="lg:pl-64">
        {{-- Topbar --}}
        <header class="sticky top-0 z-20 border-b border-line bg-surface/85 backdrop-blur supports-[backdrop-filter]:bg-surface/70">
            <div class="flex h-14 items-center gap-2 px-3 sm:px-6 lg:px-8">
                <button type="button" class="btn btn-ghost btn-icon lg:hidden" @click="openDrawer" aria-label="{{ __('Menü') }}">
                    <x-icon name="menu" class="size-5"/>
                </button>
                <a href="{{ route('home') }}" class="flex items-center gap-2 font-semibold lg:hidden">
                    @include('layouts.partials.logo', ['size' => 'size-7'])
                </a>

                <button type="button" data-search-open
                        class="ml-auto flex h-9 items-center gap-2 rounded-lg border border-line bg-surface-2/60 px-3 text-sm text-muted transition hover:border-accent/50 lg:ml-0 lg:w-80">
                    <x-icon name="search"/>
                    <span class="hidden sm:inline">{{ __('Suchen oder ID eingeben…') }}</span>
                    <span class="kbd ml-auto hidden lg:inline">Strg K</span>
                </button>

                <div class="flex items-center gap-1 lg:ml-auto">
                    @include('layouts.partials.theme-toggle')
                    @include('layouts.partials.user-menu')
                </div>
            </div>
        </header>

        @include('layouts.partials.flash')

        <main class="mx-auto w-full max-w-7xl px-4 pt-6 pb-28 sm:px-6 lg:px-8 lg:pb-10">
            @yield('content')
        </main>

        <footer class="no-print mx-auto hidden max-w-7xl px-8 pb-8 text-xs text-muted lg:block">
            @include('layouts.partials.footer-links')
        </footer>
    </div>

    {{-- Bottom-Navigation (Mobil) --}}
    @include('layouts.partials.bottom-nav')

    {{-- Globale Suche --}}
    @include('layouts.partials.search')
</div>
@stack('scripts')
</body>
</html>
