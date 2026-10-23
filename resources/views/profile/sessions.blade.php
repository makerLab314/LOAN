@extends('layouts.app')
@section('title', __('Sitzungen'))

@section('content')
    <x-page-header :title="__('Angemeldete Geräte')" :back="route('profile.edit')">
        <x-slot:subtitle>{{ __('Hier siehst du, wo dein Konto gerade angemeldet ist. Unbekannte Sitzungen bitte beenden und das Passwort ändern.') }}</x-slot:subtitle>
    </x-page-header>

    <div class="grid max-w-4xl gap-6">
        <section class="card">
            @if (! $supported)
                <x-empty icon="info" :title="__('Nicht verfügbar')">{{ __('Die Sitzungsübersicht benötigt SESSION_DRIVER=database.') }}</x-empty>
            @else
                <ul class="divide-y divide-line">
                    @foreach ($sessions as $session)
                        <li class="flex items-center gap-4 px-4 py-3.5 sm:px-5">
                            <span class="grid size-10 shrink-0 place-items-center rounded-lg bg-surface-2 text-muted">
                                <x-icon :name="$session->agent['mobile'] ? 'smartphone' : 'monitor'" class="size-5"/>
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-medium">{{ $session->agent['browser'] }} · {{ $session->agent['os'] }}
                                    @if ($session->current)<span class="badge badge-ok ml-1">{{ __('dieses Gerät') }}</span>@endif
                                </p>
                                <p class="text-xs text-muted">{{ $session->ip ?? '—' }} · {{ __('zuletzt aktiv :time', ['time' => $session->last_active->diffForHumans()]) }}</p>
                            </div>
                            @unless ($session->current)
                                <form method="POST" action="{{ route('sessions.destroy', $session->id) }}">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-danger-ghost btn-sm">{{ __('Abmelden') }}</button>
                                </form>
                            @endunless
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        @if ($supported && $sessions->count() > 1)
            <form method="POST" action="{{ route('sessions.destroy-others') }}" class="card">
                @csrf @method('DELETE')
                <div class="card-header"><h2 class="card-title">{{ __('Überall sonst abmelden') }}</h2></div>
                <div class="card-body flex flex-col gap-3 sm:flex-row sm:items-end">
                    <x-field :label="__('Passwort zur Bestätigung')" name="password" bag="sessions" class="flex-1">
                        <input type="password" name="password" class="input" required autocomplete="current-password">
                    </x-field>
                    <button class="btn btn-danger"><x-icon name="logout"/>{{ __('Alle anderen abmelden') }}</button>
                </div>
            </form>
        @endif
    </div>
@endsection
