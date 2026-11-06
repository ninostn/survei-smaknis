@extends('layouts.app')

@section('title', 'Halaman Tidak Ditemukan')

@section('content')
    <main class="flex min-h-screen items-center justify-center p-4">
        <div class="w-full max-w-sm rounded-3xl border border-white bg-white/90 p-8 text-center shadow-[0_18px_50px_-18px_rgba(150,165,205,0.45)]">
            <div class="mx-auto mb-5 flex h-12 w-12 items-center justify-center rounded-2xl bg-indigo-50 text-accent-lavender">
                <svg class="h-6 w-6 fill-current" viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M12 2a10 10 0 100 20 10 10 0 000-20zm0 18a8 8 0 110-16 8 8 0 010 16z"></path>
                    <path d="M11 7h2v7h-2zm0 8h2v2h-2z"></path>
                </svg>
            </div>
            <p class="text-xs font-semibold uppercase tracking-widest text-ink-muted">Kode 404</p>
            <h1 class="mt-2 text-xl font-bold tracking-tight text-ink">Halaman Tidak Ditemukan</h1>
            <p class="mt-2 text-sm text-ink-muted">Halaman yang Anda cari tidak ada atau sudah dipindahkan.</p>
            <a href="{{ url('/') }}"
               class="mt-6 inline-block w-full rounded-2xl bg-accent-lavender px-4 py-3 text-sm font-bold text-white transition-colors hover:bg-accent-lavender-soft focus:outline-none focus:ring-2 focus:ring-accent-lavender/40">
                Kembali ke Beranda
            </a>
        </div>
    </main>
@endsection
