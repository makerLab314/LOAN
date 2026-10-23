@extends('layouts.app')
@section('title', __('Scannen'))

@section('content')
    <x-page-header :title="__('Gerät finden')">
        <x-slot:subtitle>{{ __('QR-Code/Barcode scannen oder Inventar-ID eintippen.') }}</x-slot:subtitle>
    </x-page-header>

    <div class="mx-auto grid max-w-3xl gap-6 md:grid-cols-2">
        <section class="card overflow-hidden" data-scanner data-lookup="{{ route('lookup') }}">
            <div class="relative aspect-square bg-black/90">
                <video class="size-full object-cover" playsinline muted></video>
                <div class="pointer-events-none absolute inset-[18%] rounded-2xl border-2 border-white/70 shadow-[0_0_0_9999px_rgba(0,0,0,0.35)]"></div>
                <div class="absolute inset-0 grid place-items-center text-white/80 [.scanning_&]:hidden">
                    <div class="text-center">
                        <x-icon name="camera" class="mx-auto size-10"/>
                        <p class="mt-2 text-sm">{{ __('Kamera ist aus') }}</p>
                    </div>
                </div>
            </div>
            <div class="card-body space-y-3">
                <p class="text-sm text-muted" data-scan-status>{{ __('Tipp: Die normale Handy-Kamera erkennt die QR-Etiketten auch direkt.') }}</p>
                <button type="button" class="btn btn-primary w-full" data-scan-start><x-icon name="camera"/>{{ __('Kamera starten') }}</button>
                <button type="button" class="btn btn-secondary w-full" data-scan-stop hidden>{{ __('Stoppen') }}</button>
            </div>
        </section>

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
    </div>
@endsection
