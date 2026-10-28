@php
    $flashes = array_filter([
        'success' => session('success') ?? session('status'),
        'warning' => session('warning'),
        'error' => session('error') ?? ($errors->any() ? __('Bitte die markierten Eingaben prüfen.').($errors->count() === 1 && ! $errors->has('email') ? ' '.$errors->first() : '') : null),
    ]);
    $styles = [
        'success' => ['icon' => 'check', 'class' => 'border-ok/30 text-ok'],
        'warning' => ['icon' => 'alert', 'class' => 'border-warn/30 text-warn'],
        'error' => ['icon' => 'alert', 'class' => 'border-danger/30 text-danger'],
    ];
@endphp
@if ($flashes)
    <div class="no-print pointer-events-none fixed inset-x-0 top-16 z-50 flex flex-col items-center gap-2 px-4 sm:items-end sm:px-6" aria-live="polite">
        @foreach ($flashes as $type => $message)
            <div x-data="toast" x-show="visible" x-transition.opacity
                 class="glass-pop pointer-events-auto flex w-full max-w-sm items-start gap-3 p-3.5 {{ $styles[$type]['class'] }}"
                 role="{{ $type === 'error' ? 'alert' : 'status' }}" data-timeout="{{ $type === 'error' ? 9000 : 5000 }}">
                <x-icon :name="$styles[$type]['icon']" class="mt-0.5 size-5 shrink-0"/>
                <p class="flex-1 text-sm text-fg">{{ $message }}</p>
                <button type="button" class="text-muted hover:text-fg" @click="hide" aria-label="{{ __('Schließen') }}">
                    <x-icon name="x"/>
                </button>
            </div>
        @endforeach
    </div>
@endif
