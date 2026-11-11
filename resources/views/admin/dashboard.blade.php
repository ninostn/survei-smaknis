@extends('layouts.admin')

@section('title', 'Dashboard Admin')

@section('admin-content')
    <main class="mx-auto w-full max-w-6xl px-4 py-8">
        <div class="rounded-3xl border border-white bg-white/90 p-8 shadow-[0_18px_50px_-18px_rgba(150,165,205,0.45)]">
            <p class="text-xs font-semibold uppercase tracking-wider text-ink-muted">Dashboard Admin</p>
            <h1 class="mt-2 text-2xl font-bold tracking-tight text-ink">Halo, {{ auth()->user()->username }}</h1>
            <p class="mt-2 text-sm text-ink-muted">Beranda admin Survei SMAKNIS. Dashboard statistik menyusul.</p>

            <div class="mt-6 flex flex-wrap gap-2">
                <a href="{{ route('admin.users.index') }}"
                   class="rounded-2xl bg-accent-lavender px-4 py-2.5 text-xs font-bold text-white shadow-[0_8px_20px_-6px_rgba(155,153,233,0.5)] transition-colors hover:bg-accent-lavender-soft">
                    Manajemen Akun
                </a>
                <a href="{{ route('admin.classes.index') }}"
                   class="rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-semibold text-ink-muted transition-colors hover:border-accent-lavender hover:text-ink">
                    Kelas
                </a>
                <a href="{{ route('admin.subjects.index') }}"
                   class="rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-semibold text-ink-muted transition-colors hover:border-accent-lavender hover:text-ink">
                    Mata Pelajaran
                </a>
                <a href="{{ route('admin.teaching-assignments.index') }}"
                   class="rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-semibold text-ink-muted transition-colors hover:border-accent-lavender hover:text-ink">
                    Penugasan Mengajar
                </a>
                <a href="{{ route('surveys.index') }}"
                   class="rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-semibold text-ink-muted transition-colors hover:border-accent-lavender hover:text-ink">
                    Survei
                </a>
            </div>
        </div>
    </main>
@endsection
