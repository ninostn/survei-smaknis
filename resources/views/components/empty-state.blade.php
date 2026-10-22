@props(['title', 'description' => null])

<div class="rounded-card border border-dashed border-line bg-surface/60 px-6 py-12 text-center">
    <p class="text-sm font-medium text-white">{{ $title }}</p>
    @if ($description)
        <p class="mx-auto mt-1.5 max-w-sm text-sm text-muted">{{ $description }}</p>
    @endif
    @if (! $slot->isEmpty())
        <div class="mt-5 flex justify-center">{{ $slot }}</div>
    @endif
</div>
