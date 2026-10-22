@props(['variant' => 'primary', 'type' => 'button', 'href' => null])

@php
    $variants = [
        'primary' => 'bg-accent text-night hover:brightness-110',
        'secondary' => 'border border-line bg-surface text-white hover:bg-raised',
        'danger' => 'bg-danger text-white hover:brightness-110',
        'ghost' => 'text-muted hover:bg-raised hover:text-white',
    ];

    $classes = 'inline-flex items-center justify-center gap-2 rounded-control px-4 py-2.5 text-sm font-medium transition focus:outline-none focus-visible:ring-2 focus-visible:ring-accent focus-visible:ring-offset-2 focus-visible:ring-offset-night disabled:pointer-events-none disabled:opacity-50 '
        . ($variants[$variant] ?? $variants['primary']);
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</button>
@endif
