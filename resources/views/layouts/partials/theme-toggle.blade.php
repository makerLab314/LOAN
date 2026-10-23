{{-- System / Hell / Dunkel – gespeichert am Nutzer (bzw. lokal für Gäste) --}}
<div x-data="themeToggle" data-url="{{ auth()->check() ? route('profile.theme') : '' }}" class="relative">
    <button type="button" class="btn btn-ghost btn-icon" @click="toggle" :title="label" aria-label="{{ __('Farbschema') }}">
        <span x-show="pref === 'light'"><x-icon name="sun" class="size-5"/></span>
        <span x-show="pref === 'dark'" x-cloak><x-icon name="moon" class="size-5"/></span>
        <span x-show="pref === 'system'" x-cloak><x-icon name="monitor" class="size-5"/></span>
    </button>
    <div x-cloak x-show="open" @click.outside="close"
         class="absolute right-0 z-40 mt-1 w-40 rounded-xl border border-line bg-surface p-1 shadow-lg">
        <button type="button" class="nav-link w-full" @click="set" data-value="light"><x-icon name="sun"/>{{ __('Hell') }}</button>
        <button type="button" class="nav-link w-full" @click="set" data-value="dark"><x-icon name="moon"/>{{ __('Dunkel') }}</button>
        <button type="button" class="nav-link w-full" @click="set" data-value="system"><x-icon name="monitor"/>{{ __('System') }}</button>
    </div>
</div>
