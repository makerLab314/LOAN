@props(['id', 'title', 'openOnLoad' => false, 'size' => 'max-w-lg'])
<dialog id="{{ $id }}" {{ $attributes->merge(['class' => 'modal '.$size]) }} @if ($openOnLoad) data-open-on-load @endif aria-labelledby="{{ $id }}-title">
    <div class="flex items-center justify-between border-b border-line px-5 py-3.5">
        <h2 id="{{ $id }}-title" class="font-semibold">{{ $title }}</h2>
        <button type="button" class="btn btn-ghost btn-icon -mr-2" data-dialog-close aria-label="{{ __('Schließen') }}">
            <x-icon name="x"/>
        </button>
    </div>
    <div class="max-h-[75vh] overflow-y-auto p-5">
        {{ $slot }}
    </div>
</dialog>
