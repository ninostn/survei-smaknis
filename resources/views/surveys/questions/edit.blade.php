@extends(auth()->user()->role === 'admin' ? 'layouts.admin' : 'layouts.app')

@section('title', 'Edit Pertanyaan')

@section(auth()->user()->role === 'admin' ? 'admin-content' : 'content')
    @if (auth()->user()->role !== 'admin')
        @include('surveys._nav')
    @endif

    <div class="w-full max-w-4xl py-2">
        <a href="{{ route('surveys.questions.index', $survey) }}" class="text-xs font-semibold text-ink-muted hover:text-ink">&larr; Kembali ke daftar pertanyaan</a>

        <div class="mt-3 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
            <h1 class="mt-1 text-xl font-bold tracking-tight text-ink">Edit Pertanyaan</h1>
            <p class="mt-1 text-xs font-medium text-ink-muted">Survei: {{ $survey->title }}</p>

            @include('surveys.questions._form', ['question' => $question])
        </div>
    </div>
@endsection
