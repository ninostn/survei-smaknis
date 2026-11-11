@extends('layouts.app')

@section('title', 'Tambah Survei')

@section('content')
    @include('surveys._nav')

    <main class="mx-auto w-full max-w-2xl px-4 py-8">
        <a href="{{ route('surveys.index') }}" class="text-xs font-semibold text-ink-muted hover:text-ink">&larr; Kembali ke daftar survei</a>

        <div class="mt-3 rounded-3xl border border-white bg-white/90 p-6 shadow-[0_10px_30px_-12px_rgba(150,165,205,0.4)] sm:p-8">
            <p class="text-xs font-semibold uppercase tracking-wider text-ink-muted">Manajemen Survei</p>
            <h1 class="mt-1 text-xl font-bold tracking-tight text-ink">Tambah Survei</h1>
            <p class="mt-1 text-xs font-medium text-ink-muted">Survei baru disimpan sebagai draft. Target responden dapat diatur sekarang; tanggal pengisian diatur pada tahap berikutnya.</p>

            @include('surveys._form')
        </div>
    </main>
@endsection
