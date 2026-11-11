@extends('layouts.app')

@section('title', 'Dashboard Siswa')

@section('content')
    <main class="mx-auto flex min-h-screen w-full max-w-3xl flex-col items-start justify-center gap-6 p-6">
        <div class="w-full rounded-3xl border border-white bg-white/90 p-8 shadow-[0_18px_50px_-18px_rgba(150,165,205,0.45)]">
            <p class="text-xs font-semibold uppercase tracking-wider text-ink-muted">Dashboard Siswa</p>
            <h1 class="mt-2 text-2xl font-bold tracking-tight text-ink">Halo, {{ auth()->user()->username }}</h1>
            <p class="mt-2 text-sm text-ink-muted">Beranda siswa Survei SMAKNIS. Fitur dashboard lengkap menyusul.</p>
        </div>

        <a href="{{ route('responses.index') }}"
           class="w-full rounded-2xl bg-accent-lavender px-4 py-3 text-center text-sm font-bold text-white shadow-[0_8px_20px_-6px_rgba(155,153,233,0.5)] transition-colors hover:bg-accent-lavender-soft">
            Isi Survei
        </a>

        <form method="POST" action="{{ route('logout') }}" class="w-full">
            @csrf
            <button type="submit"
                    class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-ink-muted transition-colors hover:border-accent-lavender hover:text-ink">
                Keluar
            </button>
        </form>
    </main>
@endsection
