@props(['type' => 'info'])

@php
    $styles = [
        'danger' => 'border-danger bg-alert text-danger',
        'success' => 'border-ok bg-ok/10 text-ok',
        'info' => 'border-accent bg-accent/10 text-accent',
    ];
@endphp

<div {{ $attributes->merge(['class' => 'mb-4 rounded-control border-l-4 px-4 py-3 text-sm ' . ($styles[$type] ?? $styles['info'])]) }} role="alert">
    {{ $slot }}
</div>
