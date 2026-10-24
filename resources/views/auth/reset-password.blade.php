@extends('layouts.guest')
@section('title', __('Neues Passwort'))

@section('content')
    <div class="card p-6">
        <h1 class="text-lg font-semibold">{{ __('Neues Passwort festlegen') }}</h1>

        <form method="POST" action="{{ route('password.store') }}" class="mt-6 space-y-4">
            @csrf
            <input type="hidden" name="token" value="{{ $request->route('token') }}">
            <x-field :label="__('E-Mail')" name="email">
                <input id="email" type="email" name="email" value="{{ old('email', $request->email) }}" class="input" required autocomplete="username">
            </x-field>
            <x-field :label="__('Neues Passwort')" name="password" :hint="__('Mind. :n Zeichen, Buchstaben und Zahlen.', ['n' => max(8, (int) $settings->get('security.password_min'))])">
                <x-password id="password" name="password" required autofocus autocomplete="new-password"/>
            </x-field>
            <x-field :label="__('Wiederholen')" name="password_confirmation">
                <x-password id="password_confirmation" name="password_confirmation" required autocomplete="new-password"/>
            </x-field>
            <button class="btn btn-primary w-full">{{ __('Passwort speichern') }}</button>
        </form>
    </div>
@endsection
