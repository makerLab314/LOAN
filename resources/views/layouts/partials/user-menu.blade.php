@auth
<div x-data="dropdown" class="relative">
    <button type="button" class="flex items-center gap-2 rounded-lg p-1 pr-2 transition hover:bg-surface-2" @click="toggle" aria-haspopup="true">
        <span class="grid size-8 place-items-center rounded-full bg-accent-soft text-xs font-semibold text-accent">
            {{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}
        </span>
        <span class="hidden max-w-32 truncate text-sm font-medium md:block">{{ auth()->user()->name }}</span>
        <x-icon name="chevron-down" class="hidden size-4 text-muted md:block"/>
    </button>
    <div x-cloak x-show="open" @click.outside="close"
         class="glass-pop absolute right-0 z-40 mt-1 w-60 p-1">
        <div class="border-b border-line px-3 py-2">
            <p class="truncate text-sm font-medium">{{ auth()->user()->name }}</p>
            <p class="truncate text-xs text-muted">{{ auth()->user()->email }}</p>
            <p class="mt-1"><span class="badge badge-accent">{{ __(config('loan.roles.'.auth()->user()->role, auth()->user()->role)) }}</span></p>
        </div>
        <a href="{{ route('profile.edit') }}" class="nav-link mt-1"><x-icon name="user"/>{{ __('Profil') }}</a>
        <a href="{{ route('sessions.index') }}" class="nav-link"><x-icon name="smartphone"/>{{ __('Sitzungen') }}</a>
        <form method="POST" action="{{ route('locale.switch') }}">
            @csrf
            <input type="hidden" name="locale" value="{{ app()->getLocale() === 'de' ? 'en' : 'de' }}">
            <button type="submit" class="nav-link w-full"><x-icon name="globe"/>{{ app()->getLocale() === 'de' ? 'English' : 'Deutsch' }}</button>
        </form>
        <form method="POST" action="{{ route('logout') }}" class="border-t border-line pt-1 mt-1">
            @csrf
            <button type="submit" class="nav-link w-full text-danger hover:text-danger"><x-icon name="logout"/>{{ __('Abmelden') }}</button>
        </form>
    </div>
</div>
@endauth
