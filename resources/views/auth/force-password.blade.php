@extends('layouts.guest')
@section('title', __('Neues Passwort'))

@section('content')
    <div class="card p-6">
        <h1 class="text-lg font-semibold">{{ __('Eigenes Passwort festlegen') }}</h1>
        <p class="mt-1 text-sm text-muted">{{ __('Dein Passwort wurde von der Verwaltung vergeben. Bitte lege vor dem Weiterarbeiten ein eigenes fest – nur du kennst es dann.') }}</p>

        <form method="POST" action="{{ route('password.force.update') }}" class="mt-6 space-y-4">
            @csrf @method('PUT')
            <input type="text" name="username" value="{{ auth()->user()->email }}" autocomplete="username" class="sr-only" tabindex="-1" aria-hidden="true" readonly>
            <x-field :label="__('Neues Passwort')" name="password" :hint="__('Mind. :n Zeichen, Buchstaben und Zahlen.', ['n' => max(8, (int) $settings->get('security.password_min'))])">
                <x-password id="password" name="password" required autofocus autocomplete="new-password"/>
            </x-field>
            <x-field :label="__('Wiederholen')" name="password_confirmation">
                <x-password id="password_confirmation" name="password_confirmation" required autocomplete="new-password"/>
            </x-field>
            <button class="btn btn-primary w-full"><x-icon name="key"/>{{ __('Passwort speichern') }}</button>
        </form>

        <form method="POST" action="{{ route('logout') }}" class="mt-4 text-center">
            @csrf
            <button class="text-sm text-muted hover:text-fg hover:underline">{{ __('Abmelden') }}</button>
        </form>
    </div>
@endsection
