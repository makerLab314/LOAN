@extends('layouts.app')
@section('title', __('Profil'))

@section('content')
    <x-page-header :title="__('Profil')">
        <x-slot:actions>
            <a href="{{ route('sessions.index') }}" class="btn btn-secondary"><x-icon name="smartphone"/>{{ __('Sitzungen') }}</a>
        </x-slot:actions>
    </x-page-header>

    <div class="grid max-w-4xl gap-6">
        <form method="POST" action="{{ route('profile.update') }}" class="card">
            @csrf @method('PATCH')
            <div class="card-header"><h2 class="card-title">{{ __('Angaben') }}</h2></div>
            <div class="card-body grid gap-4 sm:grid-cols-2">
                <x-field :label="__('Name')" name="name" bag="profile">
                    <input id="name" name="name" class="input" value="{{ old('name', $user->name) }}" required maxlength="255" autocomplete="name">
                </x-field>
                <x-field :label="__('E-Mail')" name="email" bag="profile">
                    <input id="email" type="email" name="email" class="input" value="{{ old('email', $user->email) }}" required autocomplete="email">
                </x-field>
                <x-field :label="__('Sprache')" name="locale" bag="profile">
                    <select id="locale" name="locale" class="input">
                        <option value="de" @selected(old('locale', $user->locale) === 'de')>Deutsch</option>
                        <option value="en" @selected(old('locale', $user->locale) === 'en')>English</option>
                    </select>
                </x-field>
                <x-field :label="__('Aktuelles Passwort')" name="current_password" bag="profile" :hint="__('Nur nötig, wenn du die E-Mail-Adresse änderst.')">
                    <input id="current_password" type="password" name="current_password" class="input" autocomplete="current-password">
                </x-field>
            </div>
            <div class="flex justify-end border-t border-line px-4 py-3 sm:px-5">
                <button class="btn btn-primary"><x-icon name="check"/>{{ __('Speichern') }}</button>
            </div>
        </form>

        <form method="POST" action="{{ route('password.update') }}" class="card">
            @csrf @method('PUT')
            <div class="card-header">
                <div>
                    <h2 class="card-title">{{ __('Passwort ändern') }}</h2>
                    <p class="text-xs text-muted">{{ __('Danach werden alle anderen Geräte abgemeldet.') }}</p>
                </div>
            </div>
            <div class="card-body grid gap-4 sm:grid-cols-3">
                {{-- Für Passwortmanager (Bitwarden & Co.): zu welchem Konto das neue Passwort gehört --}}
                <input type="text" name="username" value="{{ $user->email }}" autocomplete="username" class="sr-only" tabindex="-1" aria-hidden="true" readonly>
                <x-field :label="__('Aktuelles Passwort')" name="current_password" bag="password" for="pw_current">
                    <x-password id="pw_current" name="current_password" required autocomplete="current-password"/>
                </x-field>
                <x-field :label="__('Neues Passwort')" name="password" bag="password" for="pw_new" :hint="__('Mind. :n Zeichen, Buchstaben und Zahlen.', ['n' => max(8, (int) $settings->get('security.password_min'))])">
                    <x-password id="pw_new" name="password" required autocomplete="new-password" minlength="8"/>
                </x-field>
                <x-field :label="__('Wiederholen')" name="password_confirmation" bag="password" for="pw_confirm">
                    <x-password id="pw_confirm" name="password_confirmation" required autocomplete="new-password"/>
                </x-field>
            </div>
            <div class="flex justify-end border-t border-line px-4 py-3 sm:px-5">
                <button class="btn btn-primary"><x-icon name="key"/>{{ __('Passwort ändern') }}</button>
            </div>
        </form>

        @if ($settings->get('security.allow_self_delete'))
            <form method="POST" action="{{ route('profile.destroy') }}" class="card border-danger/30" data-confirm="{{ __('Konto endgültig löschen?') }}">
                @csrf @method('DELETE')
                <div class="card-header"><h2 class="card-title text-danger">{{ __('Konto löschen') }}</h2></div>
                <div class="card-body flex flex-col gap-3 sm:flex-row sm:items-end">
                    <x-field :label="__('Passwort zur Bestätigung')" name="password" bag="userDeletion" class="flex-1">
                        <input type="password" name="password" class="input" required autocomplete="current-password">
                    </x-field>
                    <button class="btn btn-danger"><x-icon name="trash"/>{{ __('Konto löschen') }}</button>
                </div>
            </form>
        @endif
    </div>
@endsection
