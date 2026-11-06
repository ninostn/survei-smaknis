@extends('layouts.admin')

@section('title', 'Penugasan Mengajar')

@section('admin-content')
    <main class="mx-auto w-full max-w-6xl px-4 py-8">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-ink-muted">Data Akademik</p>
                <h1 class="mt-1 text-2xl font-bold tracking-tight text-ink">Penugasan Mengajar</h1>
                <p class="mt-1 text-xs font-medium text-ink-muted">Dasar pembatasan kelas yang dapat ditargetkan guru.</p>
            </div>
            <a href="{{ route('admin.teaching-assignments.create') }}"
               class="inline-flex items-center justify-center rounded-2xl bg-accent-lavender px-4 py-2.5 text-xs font-bold text-white shadow-[0_8px_20px_-6px_rgba(155,153,233,0.5)] transition-colors hover:bg-accent-lavender-soft">
                + Tambah Penugasan
            </a>
        </div>

        @if (session('status'))
            <div class="mt-5 rounded-2xl border border-emerald-100 bg-emerald-50 px-4 py-3 text-xs font-semibold text-emerald-700">
                {{ session('status') }}
            </div>
        @endif

        <form method="GET" action="{{ route('admin.teaching-assignments.index') }}" class="mt-5 grid grid-cols-1 gap-3 rounded-3xl border border-white bg-white/90 p-4 shadow-[0_10px_30px_-12px_rgba(150,165,205,0.4)] sm:grid-cols-2 lg:grid-cols-3">
            <select name="teacher_id" class="rounded-2xl border border-slate-200 bg-slate-50/60 px-4 py-2.5 text-xs focus:border-accent-lavender focus:bg-white focus:outline-none focus:ring-2 focus:ring-accent-lavender/30">
                <option value="">Semua guru</option>
                @foreach ($teachers as $teacher)
                    <option value="{{ $teacher->id }}" @selected($teacherId === $teacher->id)>{{ $teacher->name }}</option>
                @endforeach
            </select>

            <select name="class_id" class="rounded-2xl border border-slate-200 bg-slate-50/60 px-4 py-2.5 text-xs focus:border-accent-lavender focus:bg-white focus:outline-none focus:ring-2 focus:ring-accent-lavender/30">
                <option value="">Semua kelas</option>
                @foreach ($classes as $class)
                    <option value="{{ $class->id }}" @selected($classId === $class->id)>{{ $class->name }} — {{ $class->academic_year }}</option>
                @endforeach
            </select>

            <select name="subject_id" class="rounded-2xl border border-slate-200 bg-slate-50/60 px-4 py-2.5 text-xs focus:border-accent-lavender focus:bg-white focus:outline-none focus:ring-2 focus:ring-accent-lavender/30">
                <option value="">Semua mata pelajaran</option>
                @foreach ($subjects as $subject)
                    <option value="{{ $subject->id }}" @selected($subjectId === $subject->id)>{{ $subject->code }} — {{ $subject->name }}</option>
                @endforeach
            </select>

            <select name="academic_year" class="rounded-2xl border border-slate-200 bg-slate-50/60 px-4 py-2.5 text-xs focus:border-accent-lavender focus:bg-white focus:outline-none focus:ring-2 focus:ring-accent-lavender/30">
                <option value="">Semua tahun akademik</option>
                @foreach ($years as $option)
                    <option value="{{ $option }}" @selected($year === $option)>{{ $option }}</option>
                @endforeach
            </select>

            <div class="flex gap-2 sm:col-span-2 lg:col-span-1">
                <button type="submit" class="flex-1 rounded-2xl bg-ink px-4 py-2.5 text-xs font-bold text-white transition-colors hover:bg-slate-700">
                    Terapkan
                </button>
                <a href="{{ route('admin.teaching-assignments.index') }}" class="rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-semibold text-ink-muted transition-colors hover:text-ink">
                    Reset
                </a>
            </div>
        </form>

        <div class="mt-5 overflow-hidden rounded-3xl border border-white bg-white/90 shadow-[0_10px_30px_-12px_rgba(150,165,205,0.4)]">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[760px] text-left text-xs">
                    <thead>
                        <tr class="border-b border-slate-100 text-[11px] uppercase tracking-wider text-ink-muted">
                            <th class="px-5 py-4 font-semibold">Guru</th>
                            <th class="px-5 py-4 font-semibold">Kelas</th>
                            <th class="px-5 py-4 font-semibold">Mata Pelajaran</th>
                            <th class="px-5 py-4 font-semibold">Tahun Akademik</th>
                            <th class="px-5 py-4 text-right font-semibold">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($assignments as $assignment)
                            <tr class="border-b border-slate-50 last:border-0">
                                <td class="px-5 py-4 font-bold text-ink">{{ $assignment->teacher?->name ?? '—' }}</td>
                                <td class="px-5 py-4 font-medium text-ink-muted">
                                    {{ $assignment->schoolClass?->name ?? '—' }}
                                    @if ($assignment->schoolClass && ! $assignment->schoolClass->is_active)
                                        <span class="ml-1 rounded-lg bg-slate-100 px-2 py-0.5 text-[10px] font-semibold text-ink-muted">nonaktif</span>
                                    @endif
                                </td>
                                <td class="px-5 py-4 font-medium text-ink-muted">
                                    {{ $assignment->subject?->code ?? '—' }} — {{ $assignment->subject?->name ?? '' }}
                                </td>
                                <td class="px-5 py-4 font-medium text-ink-muted">{{ $assignment->academic_year }}</td>
                                <td class="px-5 py-4">
                                    <div class="flex justify-end gap-2">
                                        <a href="{{ route('admin.teaching-assignments.edit', $assignment) }}"
                                           class="rounded-xl border border-slate-200 bg-white px-3.5 py-2 font-bold text-ink-muted transition-colors hover:border-accent-lavender hover:text-ink">
                                            Edit
                                        </a>
                                        <form method="POST" action="{{ route('admin.teaching-assignments.destroy', $assignment) }}"
                                              onsubmit="return confirm('Hapus penugasan ini?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="rounded-xl border border-slate-200 bg-white px-3.5 py-2 font-bold text-rose-500 transition-colors hover:border-rose-300">
                                                Hapus
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-5 py-12 text-center text-ink-muted">Belum ada penugasan yang cocok.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="border-t border-slate-100 px-5 py-3">
                {{ $assignments->links() }}
            </div>
        </div>
    </main>
@endsection
