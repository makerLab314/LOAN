@extends('layouts.guest')
@section('title', __('Passwort vergessen'))

@section('content')
    <div class="card p-6">
        <h1 class="text-lg font-semibold">{{ __('Passwort vergessen') }}</h1>
        <p class="mt-1 text-sm text-muted">{{ __('Gib deine E-Mail-Adresse ein – du bekommst einen Link zum Zurücksetzen.') }}</p>

        <form method="POST" action="{{ route('password.email') }}" class="mt-6 space-y-4">
            @csrf
            <x-field :label="__('E-Mail')" name="email">
                <input id="email" type="email" name="email" value="{{ old('email') }}" class="input" required autofocus autocomplete="username">
            </x-field>
            <button class="btn btn-primary w-full">{{ __('Link senden') }}</button>
        </form>
    </div>
    <p class="mt-4 text-center text-sm"><a href="{{ route('login') }}" class="text-accent hover:underline">{{ __('Zurück zur Anmeldung') }}</a></p>
@endsection
