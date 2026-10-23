@props(['device'])
@php
    $available = $device->available_quantity;
    $total = $device->total_quantity;
@endphp
@if ($device->status === 'available')
    <span class="badge badge-ok"><span class="size-1.5 rounded-full bg-current"></span>{{ $total > 1 ? __(':n verfügbar', ['n' => $total]) : __('Verfügbar') }}</span>
@elseif ($device->status === 'partial')
    <span class="badge badge-warn"><span class="size-1.5 rounded-full bg-current"></span>{{ __(':a von :t frei', ['a' => $available, 't' => $total]) }}</span>
@else
    <span class="badge badge-danger"><span class="size-1.5 rounded-full bg-current"></span>{{ __('Verliehen') }}</span>
@endif
