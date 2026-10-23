@extends('layouts.app')
@section('title', $user->exists ? __('Nutzer bearbeiten') : __('Neuer Nutzer'))

@section('content')
    <x-page-header :title="$user->exists ? $user->name : __('Neuer Nutzer')" :back="route('admin.users.index')"/>

    <div class="grid max-w-4xl gap-6">
        <form method="POST" action="{{ $user->exists ? route('admin.users.update', $user) : route('admin.users.store') }}" class="card">
            @csrf
            @if ($user->exists) @method('PUT') @endif
            <div class="card-body grid gap-4 sm:grid-cols-2">
                <x-field :label="__('Name')" name="name">
                    <input name="name" class="input" value="{{ old('name', $user->name) }}" required maxlength="255" autocomplete="off">
                </x-field>
                <x-field :label="__('E-Mail')" name="email">
                    <input type="email" name="email" class="input" value="{{ old('email', $user->email) }}" required autocomplete="off">
                </x-field>
                <x-field :label="__('Rolle')" name="role">
                    <select name="role" class="input" @disabled($user->is(auth()->user()))>
                        @foreach (config('loan.roles') as $value => $label)
                            <option value="{{ $value }}" @selected(old('role', $user->role) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @if ($user->is(auth()->user()))
                        <input type="hidden" name="role" value="{{ $user->role }}">
                        <p class="hint">{{ __('Die eigene Rolle kann nicht geändert werden.') }}</p>
                    @endif
                </x-field>
                <x-field :label="__('Sprache')" name="locale">
                    <select name="locale" class="input">
                        <option value="de" @selected(old('locale', $user->locale ?? 'de') === 'de')>Deutsch</option>
                        <option value="en" @selected(old('locale', $user->locale) === 'en')>English</option>
                    </select>
                </x-field>
                <x-field :label="$user->exists ? __('Neues Passwort (optional)') : __('Passwort')" name="password"
                         :hint="__('Mind. :n Zeichen, Buchstaben und Zahlen.', ['n' => max(8, (int) $settings->get('security.password_min'))])">
                    <input type="password" name="password" class="input" @required(! $user->exists) autocomplete="new-password">
                </x-field>
                <x-field :label="__('Passwort wiederholen')" name="password_confirmation">
                    <input type="password" name="password_confirmation" class="input" @required(! $user->exists) autocomplete="new-password">
                </x-field>
            </div>
            <div class="flex justify-end gap-2 border-t border-line px-4 py-3 sm:px-5">
                <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">{{ __('Abbrechen') }}</a>
                <button class="btn btn-primary"><x-icon name="check"/>{{ __('Speichern') }}</button>
            </div>
        </form>

        @if ($user->exists && ! $user->is(auth()->user()))
            <section class="card">
                <div class="card-header">
                    <h2 class="card-title">{{ __('Aktive Sitzungen') }}</h2>
                    @if ($sessions->isNotEmpty())
                        <form method="POST" action="{{ route('admin.users.sessions.destroy', $user) }}" data-confirm="{{ __('Alle Sitzungen von :name beenden?', ['name' => $user->name]) }}">
                            @csrf @method('DELETE')
                            <button class="btn btn-danger-ghost btn-sm"><x-icon name="logout"/>{{ __('Alle abmelden') }}</button>
                        </form>
                    @endif
                </div>
                @if ($sessions->isEmpty())
                    <x-empty icon="smartphone" :title="__('Keine aktiven Sitzungen')"/>
                @else
                    <ul class="divide-y divide-line text-sm">
                        @foreach ($sessions as $session)
                            <li class="px-4 py-2.5 sm:px-5">
                                {{ $session->agent['browser'] }} · {{ $session->agent['os'] }}
                                <span class="text-muted">· {{ $session->ip }} · {{ $session->last_active->diffForHumans() }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>
        @endif
    </div>
@endsection
