@props(['href', 'label'])

<a href="{{ $href }}" {{ $attributes->class('inline-flex min-h-10 shrink-0 items-center justify-center gap-1.5 rounded-xl bg-blue-600 px-3 text-xs font-semibold text-white transition-colors hover:bg-blue-700 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-500') }}>
    <svg aria-hidden="true" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" class="size-3.5 shrink-0">
        <path d="M8 3v10M3 8h10" />
    </svg>
    <span>{{ $label }}</span>
</a>
