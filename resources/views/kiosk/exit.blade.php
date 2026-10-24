@extends('layouts.app')
@section('title', __('Kiosk beenden'))

@section('content')
    <div class="mx-auto max-w-md">
        <x-page-header :title="__('Kiosk beenden')" :back="route('home')">
            <x-slot:subtitle>{{ __('Meldet dieses Gerät ab. Dafür wird das Passwort des Kiosk-Kontos gebraucht.') }}</x-slot:subtitle>
        </x-page-header>

        <form method="POST" action="{{ route('kiosk.exit') }}" class="card card-body space-y-4">
            @csrf
            <x-field :label="__('Passwort')" name="password">
                <x-password id="password" name="password" required autocomplete="off"/>
            </x-field>
            <button class="btn btn-danger w-full"><x-icon name="logout"/>{{ __('Abmelden') }}</button>
        </form>
    </div>
@endsection
