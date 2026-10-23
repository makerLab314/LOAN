@if ($branding->logoUrl())
    <img src="{{ $branding->logoUrl() }}" alt="" class="{{ $size ?? 'size-8' }} rounded-lg object-contain">
@else
    <span class="{{ $size ?? 'size-8' }} grid shrink-0 place-items-center rounded-lg bg-accent text-accent-fg">
        <x-icon name="package" class="size-[60%]"/>
    </span>
@endif
<span class="truncate">{{ $branding->name() }}</span>
