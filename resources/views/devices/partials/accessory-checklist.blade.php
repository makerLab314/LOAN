{{--
    $items: list<array{key: int|string, name: string, quantity: int}>
    $legend: Überschrift
--}}
@if (! empty($items))
    <fieldset class="rounded-lg border border-line p-3">
        <legend class="px-1 text-sm font-medium">{{ $legend }}</legend>
        <input type="hidden" name="accessories_present" value="1">
        <div class="space-y-2">
            @foreach ($items as $item)
                <label class="flex items-center gap-3 text-sm">
                    <input type="hidden" name="accessories[{{ $item['key'] }}]" value="0">
                    <input type="checkbox" name="accessories[{{ $item['key'] }}]" value="1" class="checkbox" @checked($checked ?? true)>
                    <span class="flex-1">{{ $item['name'] }}</span>
                    @if ($item['quantity'] > 1)<span class="badge badge-muted">{{ $item['quantity'] }}×</span>@endif
                </label>
            @endforeach
        </div>
        @isset($hint)<p class="hint mt-2">{{ $hint }}</p>@endisset
    </fieldset>
@endif
