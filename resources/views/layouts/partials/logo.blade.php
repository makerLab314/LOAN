@if ($branding->logoUrl())
    <img src="{{ $branding->logoUrl() }}" alt="" class="{{ $size ?? 'size-8' }} rounded-lg object-contain">
@else
    <span class="{{ $size ?? 'size-8' }} hero-tile grid shrink-0 place-items-center rounded-[28%]">
        <x-icon name="package" class="size-[60%]"/>
    </span>
@endif
<span class="truncate">{{ $branding->name() }}</span>
