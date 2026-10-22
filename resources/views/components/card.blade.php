@props(['as' => 'div'])

<{{ $as }} {{ $attributes->merge(['class' => 'rounded-card border border-line bg-surface p-5']) }}>
    {{ $slot }}
</{{ $as }}>
