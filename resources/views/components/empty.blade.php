@props(['icon' => 'package', 'title'])
<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center px-6 py-12 text-center']) }}>
    <span class="mb-3 grid size-12 place-items-center rounded-full bg-surface-2 text-muted">
        <x-icon :name="$icon" class="size-6"/>
    </span>
    <p class="font-medium">{{ $title }}</p>
    @if ($slot->isNotEmpty())
        <div class="mt-1 max-w-sm text-sm text-muted">{{ $slot }}</div>
    @endif
</div>
