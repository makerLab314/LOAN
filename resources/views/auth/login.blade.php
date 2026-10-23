@extends('layouts.guest')
@section('title', __('Anmelden'))

@section('content')
    <div class="card p-6">
        <h1 class="text-lg font-semibold">{{ __('Anmelden') }}</h1>
        @if ($branding->tagline())
            <p class="mt-1 text-sm text-muted">{{ $branding->tagline() }}</p>
        @endif

        <form method="POST" action="{{ route('login') }}" class="mt-6 space-y-4">
            @csrf
            <x-field :label="__('E-Mail')" name="email">
                <input id="email" type="email" name="email" value="{{ old('email') }}" class="input @error('email') input-error @enderror"
                       required autofocus autocomplete="username" inputmode="email">
            </x-field>
            <x-field :label="__('Passwort')" name="password">
                <input id="password" type="password" name="password" class="input" required autocomplete="current-password">
            </x-field>
            <div class="flex items-center justify-between">
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" name="remember" value="1" class="checkbox" @checked(old('remember'))> {{ __('Angemeldet bleiben') }}
                </label>
                <a href="{{ route('password.request') }}" class="text-sm text-accent hover:underline">{{ __('Passwort vergessen?') }}</a>
            </div>
            <button class="btn btn-primary w-full">{{ __('Anmelden') }}</button>
        </form>
    </div>
    <p class="mt-4 text-center text-xs text-muted">{{ __('Zugänge werden von der Administration vergeben.') }}</p>
@endsection
