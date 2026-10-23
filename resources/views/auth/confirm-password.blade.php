@extends('layouts.guest')
@section('title', __('Passwort bestätigen'))

@section('content')
    <div class="card p-6">
        <h1 class="text-lg font-semibold">{{ __('Passwort bestätigen') }}</h1>
        <p class="mt-1 text-sm text-muted">{{ __('Bitte bestätige dein Passwort, um fortzufahren.') }}</p>

        <form method="POST" action="{{ route('password.confirm') }}" class="mt-6 space-y-4">
            @csrf
            <x-field :label="__('Passwort')" name="password">
                <input id="password" type="password" name="password" class="input" required autofocus autocomplete="current-password">
            </x-field>
            <button class="btn btn-primary w-full">{{ __('Bestätigen') }}</button>
        </form>
    </div>
@endsection
