{{-- Kamera-Scanner (Logik in scanner.js). Mit data-lookup öffnet ein Treffer das Gerät, sonst übernimmt die Seite. --}}
@props(['hint' => __('Tipp: Die normale Handy-Kamera erkennt die QR-Etiketten auch direkt.'), 'aspect' => 'aspect-square'])
<section {{ $attributes->merge(['class' => 'card overflow-hidden']) }} data-scanner>
    <div class="relative overflow-hidden {{ $aspect }} bg-black/90">
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
        <p class="text-sm text-muted" data-scan-status aria-live="polite">{{ $hint }}</p>
        <button type="button" class="btn btn-primary w-full" data-scan-start><x-icon name="camera"/>{{ __('Kamera starten') }}</button>
        <button type="button" class="btn btn-secondary w-full" data-scan-stop hidden>{{ __('Stoppen') }}</button>
    </div>
</section>
