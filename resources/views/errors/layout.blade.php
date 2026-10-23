<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full" data-theme="{{ auth()->user()?->theme ?? 'system' }}"
      style="--accent: {{ $branding->accent() }}; --accent-fg: {{ $branding->accentForeground() }};">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>@yield('code') · {{ $branding->name() }}</title>
    @include('layouts.partials.theme-script')
    @vite(['resources/css/app.css'])
</head>
<body class="h-full">
<main class="flex min-h-full flex-col items-center justify-center px-6 text-center">
    <p class="font-mono text-sm font-semibold text-accent">@yield('code')</p>
    <h1 class="mt-2 text-2xl font-semibold">@yield('title')</h1>
    <p class="mt-2 max-w-md text-sm text-muted">@yield('message')</p>
    <a href="{{ url('/') }}" class="btn btn-primary mt-6">{{ __('Zur Startseite') }}</a>
</main>
</body>
</html>
