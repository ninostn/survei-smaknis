@props(['label', 'value', 'hint' => null])

<div class="rounded-card border border-line bg-surface p-5">
    <p class="text-[12px] font-medium uppercase tracking-wider text-muted">{{ $label }}</p>
    <p class="kpi mt-3">{{ $value }}</p>
    @if ($hint)
        <p class="mt-2 text-[12px] text-muted">{{ $hint }}</p>
    @endif
</div>
