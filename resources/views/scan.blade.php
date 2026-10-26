@extends('layouts.app')
@section('title', __('Scannen'))

@section('content')
    <x-page-header :title="__('Gerät finden')">
        <x-slot:subtitle>{{ __('QR-Code/Barcode scannen oder Inventar-ID eintippen.') }}</x-slot:subtitle>
    </x-page-header>

    <div class="mx-auto grid max-w-3xl gap-6 md:grid-cols-2">
        <x-scanner data-lookup="{{ route('lookup') }}"/>

        <div class="space-y-6">
            <section class="card h-fit">
                <div class="card-header"><h2 class="card-title">{{ __('ID eingeben') }}</h2></div>
                <form method="POST" action="{{ route('lookup') }}" class="card-body space-y-3">
                    @csrf
                    <x-field :label="__('Inventar-ID')" name="code" :hint="__('z. B. KAB-0042 oder KAB-0042-3. Hand-Scanner tippen die ID hier ein.')">
                        <input id="code" name="code" class="input font-mono text-lg uppercase" autofocus autocomplete="off" autocapitalize="characters" spellcheck="false" required placeholder="KAB-0042-3">
                    </x-field>
                    <button class="btn btn-primary w-full"><x-icon name="search"/>{{ __('Öffnen') }}</button>
                </form>
            </section>

            {{-- Direkt weiter, wenn klar ist, was passieren soll --}}
            @if (Gate::allows('loans.create') || Gate::allows('loans.return'))
                <div class="grid gap-2 sm:grid-cols-2">
                    @can('loans.create')
                        <a href="{{ route('loans.checkout') }}" class="btn btn-secondary"><x-icon name="loans"/>{{ __('Ausleihen') }}</a>
                    @endcan
                    @can('loans.return')
                        <a href="{{ route('loans.return-scan') }}" class="btn btn-secondary"><x-icon name="undo"/>{{ __('Zurückgeben') }}</a>
                    @endcan
                </div>
            @endif
        </div>
    </div>
@endsection
