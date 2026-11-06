@extends('layouts.admin')

@section('title', 'Edit Penugasan Mengajar')

@section('admin-content')
    <main class="mx-auto w-full max-w-2xl px-4 py-8">
        <a href="{{ route('admin.teaching-assignments.index') }}" class="text-xs font-semibold text-ink-muted hover:text-ink">&larr; Kembali ke daftar penugasan</a>

        <div class="mt-3 rounded-3xl border border-white bg-white/90 p-6 shadow-[0_10px_30px_-12px_rgba(150,165,205,0.4)] sm:p-8">
            <p class="text-xs font-semibold uppercase tracking-wider text-ink-muted">Data Akademik</p>
            <h1 class="mt-1 text-xl font-bold tracking-tight text-ink">Edit Penugasan Mengajar</h1>

            @include('admin.teaching-assignments._form', ['assignment' => $assignment])
        </div>
    </main>
@endsection
