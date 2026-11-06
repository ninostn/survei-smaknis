@php($subject = $subject ?? null)

@if ($errors->any())
    <div class="mt-5 rounded-2xl border border-red-100 bg-red-50 px-4 py-3 text-xs font-medium text-red-600" role="alert">
        <ul class="space-y-1">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form method="POST"
      action="{{ $subject ? route('admin.subjects.update', $subject) : route('admin.subjects.store') }}"
      class="mt-6 space-y-4">
    @csrf
    @if ($subject)
        @method('PUT')
    @endif

    <label class="block">
        <span class="mb-1.5 block text-xs font-semibold text-ink-muted">Kode</span>
        <input type="text" name="code" value="{{ old('code', $subject?->code) }}" required
               class="w-full rounded-2xl border border-slate-200 bg-slate-50/60 px-4 py-2.5 text-sm uppercase focus:border-accent-lavender focus:bg-white focus:outline-none focus:ring-2 focus:ring-accent-lavender/30">
    </label>

    <label class="block">
        <span class="mb-1.5 block text-xs font-semibold text-ink-muted">Nama mata pelajaran</span>
        <input type="text" name="name" value="{{ old('name', $subject?->name) }}" required
               class="w-full rounded-2xl border border-slate-200 bg-slate-50/60 px-4 py-2.5 text-sm focus:border-accent-lavender focus:bg-white focus:outline-none focus:ring-2 focus:ring-accent-lavender/30">
    </label>

    <button type="submit"
            class="w-full rounded-2xl bg-accent-lavender px-4 py-3 text-sm font-bold text-white shadow-[0_8px_20px_-6px_rgba(155,153,233,0.5)] transition-colors hover:bg-accent-lavender-soft">
        {{ $subject ? 'Simpan Perubahan' : 'Simpan Mata Pelajaran' }}
    </button>
</form>
