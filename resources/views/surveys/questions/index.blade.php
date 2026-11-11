@extends('layouts.app')

@section('title', 'Pertanyaan Survei')

@section('content')
    @include('surveys._nav')

    <main class="mx-auto w-full max-w-3xl px-4 py-8">
        <a href="{{ route('surveys.index') }}" class="text-xs font-semibold text-ink-muted hover:text-ink">&larr; Kembali ke daftar survei</a>

        <div class="mt-3 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-ink-muted">Manajemen Pertanyaan</p>
                <h1 class="mt-1 text-xl font-bold tracking-tight text-ink">{{ $survey->title }}</h1>
                <p class="mt-1 text-xs font-medium text-ink-muted">Susun pertanyaan dan opsi jawaban selama survei masih draft.</p>
            </div>
            <span @class([
                'inline-flex w-fit rounded-full px-3 py-1 text-[11px] font-bold',
                'bg-slate-100 text-slate-600' => $survey->status === 'draft',
                'bg-emerald-50 text-emerald-600' => $survey->status === 'published',
                'bg-rose-50 text-rose-600' => $survey->status === 'closed',
            ])>
                {{ match ($survey->status) {
                    'published' => 'Dipublikasikan',
                    'closed' => 'Ditutup',
                    default => 'Draft',
                } }}
            </span>
        </div>

        @if (session('status'))
            <div class="mt-5 rounded-2xl border border-emerald-100 bg-emerald-50 px-4 py-3 text-xs font-semibold text-emerald-700">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mt-5 rounded-2xl border border-red-100 bg-red-50 px-4 py-3 text-xs font-medium text-red-600" role="alert">
                <ul class="space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="mt-5 flex justify-end">
            <a href="{{ route('surveys.questions.create', $survey) }}"
               class="inline-flex items-center justify-center rounded-2xl bg-accent-lavender px-4 py-2.5 text-xs font-bold text-white shadow-[0_8px_20px_-6px_rgba(155,153,233,0.5)] transition-colors hover:bg-accent-lavender-soft">
                + Tambah Pertanyaan
            </a>
        </div>

        <div class="mt-4 space-y-3">
            @forelse ($survey->questions as $question)
                <article class="rounded-3xl border border-white bg-white/90 p-5 shadow-[0_10px_30px_-12px_rgba(150,165,205,0.4)]">
                    <div class="flex items-start gap-3">
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-xs font-bold text-ink-muted">
                            {{ $loop->iteration }}
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-bold text-ink">{{ $question->question_text }}</p>
                            <div class="mt-1 flex flex-wrap items-center gap-2 text-[11px] font-semibold text-ink-muted">
                                <span class="rounded-full bg-slate-100 px-2.5 py-1">
                                    {{ match ($question->type) {
                                        'single_choice' => 'Pilihan tunggal',
                                        'multiple_choice' => 'Pilihan ganda',
                                        'rating' => 'Rating 1-5',
                                        'short_text' => 'Teks singkat',
                                        'long_text' => 'Teks panjang',
                                        default => $question->type,
                                    } }}
                                </span>
                                @if ($question->is_required)
                                    <span class="rounded-full bg-amber-50 px-2.5 py-1 text-amber-600">Wajib</span>
                                @endif
                                @if (in_array($question->type, ['single_choice', 'multiple_choice'], true))
                                    <span>{{ $question->options->count() }} opsi</span>
                                @endif
                            </div>

                            @if ($question->options->isNotEmpty())
                                <ol class="mt-3 space-y-1 text-xs text-ink-muted">
                                    @foreach ($question->options as $option)
                                        <li>{{ $loop->iteration }}. {{ $option->option_text }}</li>
                                    @endforeach
                                </ol>
                            @endif
                        </div>

                        <div class="flex shrink-0 flex-wrap items-center justify-end gap-1.5">
                            <form method="POST" action="{{ route('surveys.questions.move', [$survey, $question, 'up']) }}">
                                @csrf
                                @method('PATCH')
                                <button type="submit" @disabled($loop->first)
                                        class="rounded-xl border border-slate-200 bg-white px-2.5 py-1.5 text-[11px] font-semibold text-ink-muted transition-colors hover:border-accent-lavender hover:text-ink disabled:cursor-not-allowed disabled:opacity-40">
                                    Naik
                                </button>
                            </form>
                            <form method="POST" action="{{ route('surveys.questions.move', [$survey, $question, 'down']) }}">
                                @csrf
                                @method('PATCH')
                                <button type="submit" @disabled($loop->last)
                                        class="rounded-xl border border-slate-200 bg-white px-2.5 py-1.5 text-[11px] font-semibold text-ink-muted transition-colors hover:border-accent-lavender hover:text-ink disabled:cursor-not-allowed disabled:opacity-40">
                                    Turun
                                </button>
                            </form>
                            <a href="{{ route('surveys.questions.edit', [$survey, $question]) }}"
                               class="rounded-xl border border-slate-200 bg-white px-2.5 py-1.5 text-[11px] font-semibold text-ink-muted transition-colors hover:border-accent-lavender hover:text-ink">
                                Edit
                            </a>
                            <form method="POST" action="{{ route('surveys.questions.destroy', [$survey, $question]) }}"
                                  onsubmit="return confirm('Hapus pertanyaan ini beserta opsinya? Tindakan ini tidak dapat dibatalkan.');">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        class="rounded-xl border border-rose-200 bg-white px-2.5 py-1.5 text-[11px] font-semibold text-rose-600 transition-colors hover:bg-rose-50">
                                    Hapus
                                </button>
                            </form>
                        </div>
                    </div>
                </article>
            @empty
                <div class="rounded-3xl border border-white bg-white/90 px-5 py-12 text-center text-xs font-medium text-ink-muted shadow-[0_10px_30px_-12px_rgba(150,165,205,0.4)]">
                    Belum ada pertanyaan. Tambahkan pertanyaan pertama untuk survei ini.
                </div>
            @endforelse
        </div>
    </main>
@endsection
