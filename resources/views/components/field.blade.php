@props(['label', 'name', 'hint' => null])

<div>
    <label for="{{ $name }}" class="mb-2 block text-[13px] font-medium text-muted">{{ $label }}</label>
    {{ $slot }}
    @if ($hint)
        <p class="mt-1.5 text-[12px] text-muted">{{ $hint }}</p>
    @endif
    @error($name)
        <p class="mt-1.5 text-[12px] text-danger">{{ $message }}</p>
    @enderror
</div>
