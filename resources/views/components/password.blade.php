{{-- Passwortfeld mit Auge zum Anzeigen (Logik in app.js: [data-password-toggle]) --}}
<div class="relative">
    <input {{ $attributes->merge(['type' => 'password'])->class('input pr-11') }}>
    <button type="button" data-password-toggle aria-pressed="false"
            aria-label="{{ __('Passwort anzeigen') }}" title="{{ __('Passwort anzeigen') }}"
            data-label-show="{{ __('Passwort anzeigen') }}" data-label-hide="{{ __('Passwort verbergen') }}"
            class="absolute inset-y-0 right-0 grid w-11 place-items-center rounded-r-lg text-muted transition hover:text-fg">
        <span data-when-hidden><x-icon name="eye" class="size-5"/></span>
        <span data-when-shown hidden><x-icon name="eye-off" class="size-5"/></span>
    </button>
</div>
