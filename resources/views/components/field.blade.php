@props(['label' => null, 'name' => null, 'hint' => null, 'bag' => 'default', 'for' => null])
@php
    $key = $name ? str_replace(['[', ']'], ['.', ''], $name) : null;
    $error = $key ? $errors->getBag($bag)->first($key) : null;
@endphp
<div {{ $attributes }}>
    @if ($label)
        <label class="label" for="{{ $for ?? $name }}">{{ $label }}</label>
    @endif
    {{ $slot }}
    @if ($error)
        <p class="error">{{ $error }}</p>
    @elseif ($hint)
        <p class="hint">{{ $hint }}</p>
    @endif
</div>
