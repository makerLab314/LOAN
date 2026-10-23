{{--
    Exemplar-Auswahl als Checkbox-Chips.
    $device, $selectable: list<int>|null (null = alle), $busy: array<int, DeviceLoan>, $selected: list<int>
--}}
<div class="flex flex-wrap gap-1.5">
    @foreach (range(1, $device->total_quantity) as $n)
        @php
            $isBusy = isset($busy[$n]);
            $allowed = $selectable === null ? ! $isBusy : in_array($n, $selectable, true);
        @endphp
        <label @class([
                'relative inline-flex min-w-11 cursor-pointer items-center justify-center rounded-md border px-2 py-1.5 font-mono text-xs transition select-none',
                'border-line bg-surface hover:border-accent has-checked:border-accent has-checked:bg-accent has-checked:text-accent-fg' => $allowed,
                'cursor-not-allowed border-dashed border-line bg-surface-2 text-muted line-through' => ! $allowed,
            ])
            title="{{ $device->unitLabel($n) }}{{ $isBusy && $selectable === null ? ' – '.__('verliehen an :name', ['name' => $busy[$n]->borrower_name]) : '' }}">
            <input type="checkbox" name="units[]" value="{{ $n }}" class="sr-only" @disabled(! $allowed) @checked($allowed && in_array($n, $selected ?? [], true))>
            -{{ $n }}
        </label>
    @endforeach
</div>
