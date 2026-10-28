@extends('layouts.app')
@section('title', $user->exists ? __('Nutzer bearbeiten') : __('Neuer Nutzer'))

@section('content')
    <x-page-header :title="$user->exists ? $user->name : __('Neuer Nutzer')" :back="route('admin.users.index')"/>

    <div class="grid max-w-4xl gap-6">
        <form method="POST" action="{{ $user->exists ? route('admin.users.update', $user) : route('admin.users.store') }}" class="card">
            @csrf
            @if ($user->exists) @method('PUT') @endif
            <div class="card-body grid gap-4 sm:grid-cols-2">
                {{-- Fremdes Konto: Passwortmanager sollen hier nicht die eigenen Zugangsdaten eintragen --}}
                <x-field :label="__('Name')" name="name">
                    <input id="name" name="name" class="input" value="{{ old('name', $user->name) }}" required maxlength="255" autocomplete="off" data-bwignore data-1p-ignore data-lpignore="true">
                </x-field>
                <x-field :label="__('E-Mail')" name="email">
                    <input id="email" type="email" name="email" class="input" value="{{ old('email', $user->email) }}" required autocomplete="off" data-bwignore data-1p-ignore data-lpignore="true">
                </x-field>
                <x-field :label="__('Rolle')" name="role">
                    <select name="role" class="input" @disabled($user->is(auth()->user()))>
                        @foreach (config('loan.roles') as $value => $label)
                            <option value="{{ $value }}" @selected(old('role', $user->role) === $value)>{{ __($label) }}</option>
                        @endforeach
                    </select>
                    @if ($user->is(auth()->user()))
                        <input type="hidden" name="role" value="{{ $user->role }}">
                        <p class="hint">{{ __('Die eigene Rolle kann nicht geändert werden.') }}</p>
                    @else
                        <ul class="hint list-disc space-y-0.5 pl-4">
                            <li>{{ __('Administration und Moderation: alles – Geräte, Ausleihe und Räume (Rechte je Rolle unter Einstellungen).') }}</li>
                            <li>{{ __('Raumplanung: nur Räume, Buchungen und Kalender. Geräte, Ausleihe und Scanner sind ausgeblendet.') }}</li>
                            <li>{{ __('Kiosk: für ein fest montiertes Tablet. Bleibt dauerhaft angemeldet, Abmelden nur mit Passwort. Ein Admin kann es hier mit „Alle abmelden“ oder einem neuen Passwort abmelden.') }}</li>
                        </ul>
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
                    <x-password id="password" name="password" :required="! $user->exists" autocomplete="new-password"/>
                </x-field>
                <x-field :label="__('Passwort wiederholen')" name="password_confirmation">
                    <x-password id="password_confirmation" name="password_confirmation" :required="! $user->exists" autocomplete="new-password"/>
                </x-field>
                @unless ($user->is(auth()->user()))
                    <div class="sm:col-span-2">
                        <label class="flex items-start gap-3 rounded-lg border border-line p-3 text-sm has-checked:border-accent has-checked:bg-accent-soft">
                            <input type="hidden" name="must_change_password" value="0">
                            <input type="checkbox" name="must_change_password" value="1" class="checkbox mt-0.5" @checked(old('must_change_password', '1'))>
                            <span>
                                {{ $user->exists ? __('Beim nächsten Anmelden ein eigenes Passwort verlangen') : __('Bei der ersten Anmeldung ein eigenes Passwort verlangen') }}
                                <span class="block text-xs text-muted">
                                    {{ $user->exists ? __('Gilt nur, wenn oben ein neues Passwort gesetzt wird.') : __('Empfohlen – dann kennt nur die Person selbst ihr Passwort.') }}
                                    {{ __('Für Kiosk-Konten nicht nötig.') }}
                                </span>
                            </span>
                        </label>
                        @if ($user->exists && $user->must_change_password)
                            <p class="hint">{{ __('Diese Person hat ihr Passwort noch nicht geändert.') }}</p>
                        @endif
                    </div>
                @endunless
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
                    {{-- Kiosk: auch ohne aktive Sitzung, denn das Remember-Cookie meldet sonst wieder an --}}
                    @if ($sessions->isNotEmpty() || $user->isKiosk())
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
