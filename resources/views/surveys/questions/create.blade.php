@extends('layouts.app')

@section('title', 'Tambah Pertanyaan')

@section('content')
    @include('surveys._nav')

    <main class="mx-auto w-full max-w-2xl px-4 py-8">
        <a href="{{ route('surveys.questions.index', $survey) }}" class="text-xs font-semibold text-ink-muted hover:text-ink">&larr; Kembali ke daftar pertanyaan</a>

        <div class="mt-3 rounded-3xl border border-white bg-white/90 p-6 shadow-[0_10px_30px_-12px_rgba(150,165,205,0.4)] sm:p-8">
            <p class="text-xs font-semibold uppercase tracking-wider text-ink-muted">Manajemen Pertanyaan</p>
            <h1 class="mt-1 text-xl font-bold tracking-tight text-ink">Tambah Pertanyaan</h1>
            <p class="mt-1 text-xs font-medium text-ink-muted">Survei: {{ $survey->title }}</p>

            @include('surveys.questions._form', ['question' => null])
        </div>
    </main>
@endsection
