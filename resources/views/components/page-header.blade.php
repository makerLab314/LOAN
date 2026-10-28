@props(['title', 'back' => null, 'backList' => null])
<div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
    <div class="min-w-0">
        @if ($back)
            {{-- backList: zurück zur Liste mit den zuletzt gesetzten Filtern (siehe filters.js) --}}
            <a href="{{ $back }}" @if ($backList) data-back-list="{{ $backList }}" @endif class="mb-2 inline-flex items-center gap-1 text-sm text-muted hover:text-fg">
                <x-icon name="chevron-left"/>{{ __('Zurück') }}
            </a>
        @endif
        <h1 class="truncate text-2xl font-semibold tracking-[-0.025em] sm:text-[1.875rem] sm:leading-9">{{ $title }}</h1>
        @isset($subtitle)
            <div class="mt-1 text-sm text-muted">{{ $subtitle }}</div>
        @endisset
    </div>
    @isset($actions)
        <div class="flex flex-wrap items-center gap-2">{{ $actions }}</div>
    @endisset
</div>
