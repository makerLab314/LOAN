@props(['icon' => 'package', 'title'])
<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center px-6 py-12 text-center']) }}>
    <span class="icon-tile mb-3 size-12 rounded-2xl">
        <x-icon :name="$icon" class="size-6"/>
    </span>
    <p class="font-medium">{{ $title }}</p>
    @if ($slot->isNotEmpty())
        <div class="mt-1 max-w-sm text-sm text-muted">{{ $slot }}</div>
    @endif
</div>
