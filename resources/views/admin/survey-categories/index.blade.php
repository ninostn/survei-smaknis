@extends('layouts.admin')

@section('title', 'Kategori Survei')

@section('admin-content')
    <main class="mx-auto w-full max-w-6xl px-4 py-8">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-ink-muted">Manajemen Survei</p>
                <h1 class="mt-1 text-2xl font-bold tracking-tight text-ink">Kategori Survei</h1>
                <p class="mt-1 text-xs font-medium text-ink-muted">Kelola kategori yang dipakai untuk mengelompokkan survei.</p>
            </div>
            <a href="{{ route('admin.survey-categories.create') }}"
               class="inline-flex items-center justify-center rounded-2xl bg-accent-lavender px-4 py-2.5 text-xs font-bold text-white shadow-[0_8px_20px_-6px_rgba(155,153,233,0.5)] transition-colors hover:bg-accent-lavender-soft">
                + Tambah Kategori
            </a>
        </div>

        @if (session('status'))
            <div class="mt-5 rounded-2xl border border-emerald-100 bg-emerald-50 px-4 py-3 text-xs font-semibold text-emerald-700">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mt-5 rounded-2xl border border-red-100 bg-red-50 px-4 py-3 text-xs font-medium text-red-600" role="alert">
                <ul class="space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="GET" action="{{ route('admin.survey-categories.index') }}" class="mt-5 flex flex-col gap-3 rounded-3xl border border-white bg-white/90 p-4 shadow-[0_10px_30px_-12px_rgba(150,165,205,0.4)] sm:flex-row">
            <input type="search" name="search" value="{{ e($search) }}" placeholder="Cari nama kategori..."
                   class="flex-1 rounded-2xl border border-slate-200 bg-slate-50/60 px-4 py-2.5 text-xs focus:border-accent-lavender focus:bg-white focus:outline-none focus:ring-2 focus:ring-accent-lavender/30">

            <div class="flex gap-2">
                <button type="submit" class="rounded-2xl bg-ink px-4 py-2.5 text-xs font-bold text-white transition-colors hover:bg-slate-700">
                    Terapkan
                </button>
                <a href="{{ route('admin.survey-categories.index') }}" class="rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-semibold text-ink-muted transition-colors hover:text-ink">
                    Reset
                </a>
            </div>
        </form>

        <div class="mt-5 overflow-hidden rounded-3xl border border-white bg-white/90 shadow-[0_10px_30px_-12px_rgba(150,165,205,0.4)]">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[720px] text-left text-xs">
                    <thead>
                        <tr class="border-b border-slate-100 text-[11px] uppercase tracking-wider text-ink-muted">
                            <th class="px-5 py-4 font-semibold">Nama</th>
                            <th class="px-5 py-4 font-semibold">Deskripsi</th>
                            <th class="px-5 py-4 font-semibold">Survei</th>
                            <th class="px-5 py-4 text-right font-semibold">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($categories as $category)
                            <tr class="border-b border-slate-50 last:border-0">
                                <td class="px-5 py-4 font-bold text-ink">{{ $category->name }}</td>
                                <td class="px-5 py-4 font-medium text-ink-muted">{{ $category->description ?: '—' }}</td>
                                <td class="px-5 py-4 font-medium text-ink-muted">{{ $category->surveys_count }}</td>
                                <td class="px-5 py-4">
                                    <div class="flex justify-end gap-2">
                                        <a href="{{ route('admin.survey-categories.edit', $category) }}"
                                           class="rounded-xl border border-slate-200 bg-white px-3.5 py-2 font-bold text-ink-muted transition-colors hover:border-accent-lavender hover:text-ink">
                                            Edit
                                        </a>
                                        @if ($category->surveys_count === 0)
                                            <form method="POST" action="{{ route('admin.survey-categories.destroy', $category) }}"
                                                  onsubmit="return confirm('Hapus kategori ini?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                        class="rounded-xl border border-slate-200 bg-white px-3.5 py-2 font-bold text-rose-500 transition-colors hover:border-rose-300">
                                                    Hapus
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-5 py-12 text-center text-ink-muted">Belum ada kategori yang cocok.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="border-t border-slate-100 px-5 py-3">
                {{ $categories->links() }}
            </div>
        </div>
    </main>
@endsection
