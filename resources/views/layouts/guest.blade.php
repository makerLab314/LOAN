<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full" data-theme="system"
      style="--accent: {{ $branding->accent() }}; --accent-fg: {{ $branding->accentForeground() }};">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="{{ $branding->accent() }}">
    <meta name="robots" content="noindex">
    <title>@hasSection('title')@yield('title') · @endif{{ $branding->name() }}</title>
    <link rel="icon" href="{{ $branding->faviconUrl() }}">
    @include('layouts.partials.theme-script')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full">
<div class="flex min-h-full flex-col items-center justify-center px-4 py-10">
    <div class="absolute top-4 right-4 flex items-center gap-1">
        @include('layouts.partials.theme-toggle')
        <form method="POST" action="{{ route('locale.switch') }}">
            @csrf
            <input type="hidden" name="locale" value="{{ app()->getLocale() === 'de' ? 'en' : 'de' }}">
            <button class="btn btn-ghost btn-sm">{{ app()->getLocale() === 'de' ? 'EN' : 'DE' }}</button>
        </form>
    </div>

    <a href="{{ url('/') }}" class="mb-8 flex items-center gap-3 text-xl font-semibold">
        @include('layouts.partials.logo', ['size' => 'size-10'])
    </a>

    <div class="w-full max-w-sm">
        @if (session('status'))
            <div class="mb-4 rounded-xl border border-ok/30 bg-ok/10 p-3 text-sm text-ok" role="status">{{ session('status') }}</div>
        @endif
        @yield('content')
    </div>

    <footer class="mt-10 text-xs text-muted">
        @include('layouts.partials.footer-links')
    </footer>
</div>
</body>
</html>
