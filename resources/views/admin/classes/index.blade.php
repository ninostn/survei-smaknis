@extends('layouts.admin')

@section('title', 'Manajemen Kelas')

@section('admin-content')
    <main class="mx-auto w-full max-w-6xl px-4 py-8">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-ink-muted">Data Akademik</p>
                <h1 class="mt-1 text-2xl font-bold tracking-tight text-ink">Kelas</h1>
                <p class="mt-1 text-xs font-medium text-ink-muted">Kelola kelas per tahun akademik.</p>
            </div>
            <a href="{{ route('admin.classes.create') }}"
               class="inline-flex items-center justify-center rounded-2xl bg-accent-lavender px-4 py-2.5 text-xs font-bold text-white shadow-[0_8px_20px_-6px_rgba(155,153,233,0.5)] transition-colors hover:bg-accent-lavender-soft">
                + Tambah Kelas
            </a>
        </div>

        @if (session('status'))
            <div class="mt-5 rounded-2xl border border-emerald-100 bg-emerald-50 px-4 py-3 text-xs font-semibold text-emerald-700">
                {{ session('status') }}
            </div>
        @endif

        <form method="GET" action="{{ route('admin.classes.index') }}" class="mt-5 grid grid-cols-1 gap-3 rounded-3xl border border-white bg-white/90 p-4 shadow-[0_10px_30px_-12px_rgba(150,165,205,0.4)] sm:grid-cols-2 lg:grid-cols-4">
            <input type="search" name="search" value="{{ e($search) }}" placeholder="Cari nama kelas..."
                   class="rounded-2xl border border-slate-200 bg-slate-50/60 px-4 py-2.5 text-xs focus:border-accent-lavender focus:bg-white focus:outline-none focus:ring-2 focus:ring-accent-lavender/30">

            <select name="academic_year" class="rounded-2xl border border-slate-200 bg-slate-50/60 px-4 py-2.5 text-xs focus:border-accent-lavender focus:bg-white focus:outline-none focus:ring-2 focus:ring-accent-lavender/30">
                <option value="">Semua tahun akademik</option>
                @foreach ($years as $option)
                    <option value="{{ $option }}" @selected($year === $option)>{{ $option }}</option>
                @endforeach
            </select>

            <select name="status" class="rounded-2xl border border-slate-200 bg-slate-50/60 px-4 py-2.5 text-xs focus:border-accent-lavender focus:bg-white focus:outline-none focus:ring-2 focus:ring-accent-lavender/30">
                <option value="">Semua status</option>
                <option value="active" @selected($status === 'active')>Aktif</option>
                <option value="inactive" @selected($status === 'inactive')>Nonaktif</option>
            </select>

            <div class="flex gap-2">
                <button type="submit" class="flex-1 rounded-2xl bg-ink px-4 py-2.5 text-xs font-bold text-white transition-colors hover:bg-slate-700">
                    Terapkan
                </button>
                <a href="{{ route('admin.classes.index') }}" class="rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-semibold text-ink-muted transition-colors hover:text-ink">
                    Reset
                </a>
            </div>
        </form>

        <div class="mt-5 overflow-hidden rounded-3xl border border-white bg-white/90 shadow-[0_10px_30px_-12px_rgba(150,165,205,0.4)]">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[720px] text-left text-xs">
                    <thead>
                        <tr class="border-b border-slate-100 text-[11px] uppercase tracking-wider text-ink-muted">
                            <th class="px-5 py-4 font-semibold">Nama Kelas</th>
                            <th class="px-5 py-4 font-semibold">Tahun Akademik</th>
                            <th class="px-5 py-4 font-semibold">Siswa</th>
                            <th class="px-5 py-4 font-semibold">Penugasan</th>
                            <th class="px-5 py-4 font-semibold">Status</th>
                            <th class="px-5 py-4 text-right font-semibold">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($classes as $class)
                            <tr class="border-b border-slate-50 last:border-0">
                                <td class="px-5 py-4 font-bold text-ink">{{ $class->name }}</td>
                                <td class="px-5 py-4 font-medium text-ink-muted">{{ $class->academic_year }}</td>
                                <td class="px-5 py-4 font-medium text-ink-muted">{{ $class->students_count }}</td>
                                <td class="px-5 py-4 font-medium text-ink-muted">{{ $class->teaching_assignments_count }}</td>
                                <td class="px-5 py-4">
                                    <span @class([
                                        'inline-block h-2.5 w-2.5 rounded-full',
                                        'bg-emerald-400' => $class->is_active,
                                        'bg-slate-300' => ! $class->is_active,
                                    ])></span>
                                    <span class="ml-1.5 font-semibold {{ $class->is_active ? 'text-emerald-600' : 'text-ink-muted' }}">
                                        {{ $class->is_active ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex justify-end gap-2">
                                        <a href="{{ route('admin.classes.edit', $class) }}"
                                           class="rounded-xl border border-slate-200 bg-white px-3.5 py-2 font-bold text-ink-muted transition-colors hover:border-accent-lavender hover:text-ink">
                                            Edit
                                        </a>
                                        <form method="POST" action="{{ route('admin.classes.toggle', $class) }}"
                                              onsubmit="return confirm('Ubah status kelas ini?');">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit"
                                                    class="rounded-xl border border-slate-200 bg-white px-3.5 py-2 font-bold {{ $class->is_active ? 'text-rose-500 hover:border-rose-300' : 'text-emerald-600 hover:border-emerald-300' }}">
                                                {{ $class->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-12 text-center text-ink-muted">Belum ada kelas yang cocok.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="border-t border-slate-100 px-5 py-3">
                {{ $classes->links() }}
            </div>
        </div>
    </main>
@endsection
