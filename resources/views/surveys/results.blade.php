@extends('layouts.app')

@section('title', 'Hasil Survei: ' . $survey->title)

@section('content')
    @if (isset($isRespondentView) && $isRespondentView)
        @include('surveys._nav')
    @else
        @include('surveys._nav')
    @endif

    <main class="mx-auto w-full max-w-4xl px-4 py-8">
        <a href="{{ isset($isRespondentView) && $isRespondentView ? route('responses.index') : route('surveys.index') }}" 
           class="text-xs font-semibold text-ink-muted hover:text-ink">&larr; Kembali ke daftar survei</a>

        @if (session('status'))
            <div class="mt-5 rounded-2xl border border-emerald-100 bg-emerald-50 px-4 py-3 text-xs font-semibold text-emerald-700">
                {{ session('status') }}
            </div>
        @endif

        <div class="mt-3 rounded-3xl border border-white bg-white/90 p-6 shadow-[0_10px_30px_-12px_rgba(150,165,205,0.4)]">
            <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-bold text-ink-muted">
                {{ $survey->category?->name ?? 'Tanpa kategori' }}
            </span>
            <h1 class="mt-2 text-2xl font-bold tracking-tight text-ink">{{ $survey->title }}</h1>

            @if ($survey->description)
                <p class="mt-2 text-sm font-medium text-ink-muted">{{ $survey->description }}</p>
            @endif

            <dl class="mt-4 grid gap-2 text-xs font-medium text-ink-muted sm:grid-cols-2">
                <div class="flex gap-1">
                    <dt>Status</dt>
                    <dd class="font-semibold text-ink">
                        @if ($survey->status === 'draft')
                            Draft
                        @elseif ($survey->status === 'published')
                            Dipublikasikan
                        @else
                            Ditutup
                        @endif
                    </dd>
                </div>
                <div class="flex gap-1">
                    <dt>Total Respons</dt>
                    <dd class="font-semibold text-ink">{{ $totalResponses }}</dd>
                </div>
                <div class="flex gap-1">
                    <dt>Periode</dt>
                    <dd class="font-semibold text-ink">
                        {{ $survey->starts_at?->format('d/m/Y') ?? '-' }} s/d {{ $survey->ends_at?->format('d/m/Y') ?? '-' }}
                    </dd>
                </div>
                @if (!isset($isRespondentView) || !$isRespondentView)
                    <div class="flex gap-1">
                        <dt>Pembuat</dt>
                        <dd class="font-semibold text-ink">{{ $survey->creator?->username ?? '-' }}</dd>
                    </div>
                @endif
            </dl>

            @if (!isset($isRespondentView) || !$isRespondentView)
                <div class="mt-4 flex items-center justify-between rounded-2xl border border-slate-100 bg-slate-50 p-4">
                    <div>
                        <p class="text-xs font-bold text-ink">Status Publikasi Hasil</p>
                        <p class="mt-1 text-[11px] font-medium text-ink-muted">
                            @if ($survey->is_result_published)
                                Hasil dipublikasikan &mdash; responden dapat melihat hasil agregat
                            @else
                                Hasil disembunyikan &mdash; hanya admin dan pembuat yang dapat melihat
                            @endif
                        </p>
                    </div>
                    <div>
                        @if ($survey->is_result_published)
                            <form method="POST" action="{{ route('surveys.results.publication.update', $survey) }}"
                                  onsubmit="return confirm('Sembunyikan hasil dari responden?');">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="is_result_published" value="0">
                                <button type="submit"
                                        class="inline-flex rounded-xl border border-amber-200 bg-white px-3 py-1.5 text-[11px] font-semibold text-amber-600 transition-colors hover:bg-amber-50">
                                    Sembunyikan Hasil
                                </button>
                            </form>
                        @else
                            <form method="POST" action="{{ route('surveys.results.publication.update', $survey) }}"
                                  onsubmit="return confirm('Publikasikan hasil ke responden?');">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="is_result_published" value="1">
                                <button type="submit"
                                        class="inline-flex rounded-xl border border-emerald-200 bg-white px-3 py-1.5 text-[11px] font-semibold text-emerald-600 transition-colors hover:bg-emerald-50">
                                    Publikasikan Hasil
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            @endif
        </div>

        @if ($totalResponses === 0)
            <div class="mt-5 rounded-2xl border border-slate-100 bg-slate-50 px-4 py-8 text-center">
                <p class="text-sm font-semibold text-ink-muted">Belum ada respons untuk survei ini.</p>
            </div>
        @else
            <h2 class="mt-6 text-sm font-bold text-ink">Hasil Survei</h2>

            @foreach ($results as $result)
                @php
                    $question = $result['question'];
                    $aggregate = $result['aggregate'];
                @endphp

                <article class="mt-3 rounded-3xl border border-white bg-white/90 p-5 shadow-[0_10px_30px_-12px_rgba(150,165,205,0.4)]">
                    <div class="flex items-start gap-3">
                        <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-accent-lavender text-[11px] font-bold text-white">
                            {{ $question->position }}
                        </span>
                        <div class="min-w-0 flex-1">
                            <h3 class="text-sm font-bold text-ink">
                                {{ $question->question_text }}
                                @if ($question->is_required)
                                    <span class="text-red-500">*</span>
                                @else
                                    <span class="ml-1 text-xs font-medium text-ink-muted">(Opsional)</span>
                                @endif
                            </h3>
                            <p class="mt-1 text-xs font-medium text-ink-muted">
                                @if ($aggregate['type'] === 'single_choice')
                                    Pilihan tunggal
                                @elseif ($aggregate['type'] === 'multiple_choice')
                                    Pilihan ganda
                                @elseif ($aggregate['type'] === 'rating')
                                    Rating
                                @else
                                    Jawaban teks
                                @endif
                                · {{ $aggregate['total_responses'] }}
                                @if ($aggregate['type'] === 'multiple_choice')
                                    responden menjawab
                                @else
                                    jawaban
                                @endif
                            </p>

                            @if ($aggregate['total_responses'] === 0)
                                <p class="mt-3 text-xs text-ink-muted">Tidak ada jawaban untuk pertanyaan ini.</p>
                            @elseif ($aggregate['type'] === 'single_choice' || $aggregate['type'] === 'multiple_choice')
                                @if ($aggregate['is_multiple'])
                                    <p class="mt-2 text-[11px] italic text-ink-muted">Responden dapat memilih lebih dari satu jawaban.</p>
                                @endif
                                <div class="mt-3 space-y-2">
                                    @foreach ($aggregate['options'] as $option)
                                        <div>
                                            <div class="flex items-center justify-between text-xs">
                                                <span class="font-medium text-ink">{{ $option['text'] }}</span>
                                                <span class="font-semibold text-ink-muted">{{ $option['count'] }} ({{ $option['percentage'] }}%)</span>
                                            </div>
                                            <div class="mt-1 h-2 overflow-hidden rounded-full bg-slate-100">
                                                <div class="h-full bg-accent-lavender transition-all" style="width: {{ $option['percentage'] }}%"></div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @elseif ($aggregate['type'] === 'rating')
                                <div class="mt-3 rounded-2xl border border-slate-100 bg-slate-50 p-4">
                                    <div class="text-center">
                                        <div class="text-3xl font-bold text-ink">{{ $aggregate['average'] }}</div>
                                        <div class="mt-1 text-xs font-medium text-ink-muted">dari 5</div>
                                    </div>
                                    <div class="mt-4 space-y-1.5">
                                        @for ($i = 5; $i >= 1; $i--)
                                            <div class="flex items-center gap-2">
                                                <span class="w-8 text-xs font-semibold text-ink">{{ $i }} ★</span>
                                                <div class="h-2 flex-1 overflow-hidden rounded-full bg-slate-100">
                                                    @php
                                                        $pct = $aggregate['total_responses'] > 0 ? ($aggregate['distribution'][$i] / $aggregate['total_responses']) * 100 : 0;
                                                    @endphp
                                                    <div class="h-full bg-accent-lavender transition-all" style="width: {{ round($pct, 1) }}%"></div>
                                                </div>
                                                <span class="w-8 text-right text-xs font-medium text-ink-muted">{{ $aggregate['distribution'][$i] }}</span>
                                            </div>
                                        @endfor
                                    </div>
                                </div>
                            @else
                                <div class="mt-3 space-y-2">
                                    @if (isset($hideTextAnswers) && $hideTextAnswers)
                                        <div class="rounded-2xl border border-slate-100 bg-slate-50 p-4 text-center">
                                            <p class="text-xs font-medium text-ink-muted">
                                                Jawaban teks akan ditampilkan setelah minimal 5 respons terkumpul untuk menjaga privasi responden.
                                            </p>
                                        </div>
                                    @else
                                        @forelse ($aggregate['answers'] as $index => $answer)
                                            <div class="rounded-2xl border border-slate-100 bg-slate-50 p-3">
                                                <p class="text-xs font-medium text-ink">{{ $answer->answer_text }}</p>
                                            </div>
                                        @empty
                                            <p class="text-xs text-ink-muted">Tidak ada jawaban teks.</p>
                                        @endforelse

                                        @if ($aggregate['answers']->hasPages())
                                            <div class="mt-4">
                                                {{ $aggregate['answers']->links() }}
                                            </div>
                                        @endif
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                </article>
            @endforeach
        @endif
    </main>
@endsection
