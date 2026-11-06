@extends('layouts.admin')

@section('title', 'Mata Pelajaran')

@section('admin-content')
    <main class="mx-auto w-full max-w-6xl px-4 py-8">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-ink-muted">Data Akademik</p>
                <h1 class="mt-1 text-2xl font-bold tracking-tight text-ink">Mata Pelajaran</h1>
                <p class="mt-1 text-xs font-medium text-ink-muted">Daftar mata pelajaran yang dipakai pada penugasan mengajar.</p>
            </div>
            <a href="{{ route('admin.subjects.create') }}"
               class="inline-flex items-center justify-center rounded-2xl bg-accent-lavender px-4 py-2.5 text-xs font-bold text-white shadow-[0_8px_20px_-6px_rgba(155,153,233,0.5)] transition-colors hover:bg-accent-lavender-soft">
                + Tambah Mata Pelajaran
            </a>
        </div>

        @if (session('status'))
            <div class="mt-5 rounded-2xl border border-emerald-100 bg-emerald-50 px-4 py-3 text-xs font-semibold text-emerald-700">
                {{ session('status') }}
            </div>
        @endif

        <form method="GET" action="{{ route('admin.subjects.index') }}" class="mt-5 flex flex-col gap-3 rounded-3xl border border-white bg-white/90 p-4 shadow-[0_10px_30px_-12px_rgba(150,165,205,0.4)] sm:flex-row">
            <input type="search" name="search" value="{{ e($search) }}" placeholder="Cari kode atau nama..."
                   class="flex-1 rounded-2xl border border-slate-200 bg-slate-50/60 px-4 py-2.5 text-xs focus:border-accent-lavender focus:bg-white focus:outline-none focus:ring-2 focus:ring-accent-lavender/30">

            <div class="flex gap-2">
                <button type="submit" class="rounded-2xl bg-ink px-4 py-2.5 text-xs font-bold text-white transition-colors hover:bg-slate-700">
                    Terapkan
                </button>
                <a href="{{ route('admin.subjects.index') }}" class="rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-semibold text-ink-muted transition-colors hover:text-ink">
                    Reset
                </a>
            </div>
        </form>

        <div class="mt-5 overflow-hidden rounded-3xl border border-white bg-white/90 shadow-[0_10px_30px_-12px_rgba(150,165,205,0.4)]">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[620px] text-left text-xs">
                    <thead>
                        <tr class="border-b border-slate-100 text-[11px] uppercase tracking-wider text-ink-muted">
                            <th class="px-5 py-4 font-semibold">Kode</th>
                            <th class="px-5 py-4 font-semibold">Nama</th>
                            <th class="px-5 py-4 font-semibold">Penugasan</th>
                            <th class="px-5 py-4 text-right font-semibold">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($subjects as $subject)
                            <tr class="border-b border-slate-50 last:border-0">
                                <td class="px-5 py-4 font-bold text-ink">{{ $subject->code }}</td>
                                <td class="px-5 py-4 font-medium text-ink-muted">{{ $subject->name }}</td>
                                <td class="px-5 py-4 font-medium text-ink-muted">{{ $subject->teaching_assignments_count }}</td>
                                <td class="px-5 py-4">
                                    <div class="flex justify-end">
                                        <a href="{{ route('admin.subjects.edit', $subject) }}"
                                           class="rounded-xl border border-slate-200 bg-white px-3.5 py-2 font-bold text-ink-muted transition-colors hover:border-accent-lavender hover:text-ink">
                                            Edit
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-5 py-12 text-center text-ink-muted">Belum ada mata pelajaran yang cocok.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="border-t border-slate-100 px-5 py-3">
                {{ $subjects->links() }}
            </div>
        </div>
    </main>
@endsection
