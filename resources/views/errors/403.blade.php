@extends('layouts.app')

@section('title', 'Akses Ditolak')

@section('content')
    <main class="flex min-h-screen items-center justify-center p-4">
        <div class="w-full max-w-sm rounded-3xl border border-white bg-white/90 p-8 text-center shadow-[0_18px_50px_-18px_rgba(150,165,205,0.45)]">
            <div class="mx-auto mb-5 flex h-12 w-12 items-center justify-center rounded-2xl bg-red-50 text-red-500">
                <svg class="h-6 w-6 fill-current" viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"></path>
                </svg>
            </div>
            <p class="text-xs font-semibold uppercase tracking-widest text-ink-muted">Kode 403</p>
            <h1 class="mt-2 text-xl font-bold tracking-tight text-ink">Akses Ditolak</h1>
            <p class="mt-2 text-sm text-ink-muted">Anda tidak memiliki izin untuk membuka halaman ini.</p>
            <a href="{{ url('/') }}"
               class="mt-6 inline-block w-full rounded-2xl bg-accent-lavender px-4 py-3 text-sm font-bold text-white transition-colors hover:bg-accent-lavender-soft focus:outline-none focus:ring-2 focus:ring-accent-lavender/40">
                Kembali ke Beranda
            </a>
        </div>
    </main>
@endsection
