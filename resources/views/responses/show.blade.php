@extends('layouts.app')

@section('title', $survey->title)

@section('content')
    @include('surveys._nav')

    <main class="mx-auto w-full max-w-3xl px-4 py-8">
        <a href="{{ route('responses.index') }}" class="text-xs font-semibold text-ink-muted hover:text-ink">&larr; Kembali ke daftar survei</a>

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
                    <dt>Mulai</dt>
                    <dd class="font-semibold text-ink">{{ $survey->starts_at?->format('d/m/Y H:i') ?? '-' }}</dd>
                </div>
                <div class="flex gap-1">
                    <dt>Berakhir</dt>
                    <dd class="font-semibold text-ink">{{ $survey->ends_at?->format('d/m/Y H:i') ?? '-' }}</dd>
                </div>
                <div class="flex gap-1">
                    <dt>Pembuat</dt>
                    <dd class="font-semibold text-ink">{{ $survey->creator?->username ?? '-' }}</dd>
                </div>
                <div class="flex gap-1">
                    <dt>Jumlah pertanyaan</dt>
                    <dd class="font-semibold text-ink">{{ $survey->questions->count() }}</dd>
                </div>
            </dl>
        </div>

        @if ($errors->any())
            <div class="mt-5 rounded-2xl border border-red-100 bg-red-50 px-4 py-3 text-xs font-medium text-red-600" role="alert">
                <ul class="space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <h2 class="mt-6 text-sm font-bold text-ink">Daftar Pertanyaan</h2>

        <form method="POST"
              action="{{ route('responses.store', $survey) }}"
              class="mt-3 space-y-3"
              onsubmit="return confirm('Kirim jawaban survei ini? Jawaban yang sudah dikirim tidak dapat diubah.');">
            @csrf
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
                                <span @class([
                                    'rounded-full px-2.5 py-1',
                                    'bg-amber-50 text-amber-600' => $question->is_required,
                                    'bg-slate-100 text-ink-muted' => ! $question->is_required,
                                ])>
                                    {{ $question->is_required ? 'Wajib' : 'Opsional' }}
                                </span>
                            </div>

                            @if ($question->type === 'single_choice')
                                <div class="mt-3 space-y-2">
                                    @foreach ($question->options as $option)
                                        <label class="flex cursor-pointer items-center gap-3 rounded-2xl border border-slate-200 bg-white px-3 py-2.5 text-xs font-medium text-ink transition-colors hover:border-accent-lavender">
                                            <input type="radio"
                                                   name="answers[{{ $question->id }}][option_id]"
                                                   value="{{ $option->id }}"
                                                   @checked((string) old("answers.{$question->id}.option_id") === (string) $option->id)
                                                   class="h-4 w-4 border-slate-300 text-accent-lavender focus:ring-accent-lavender">
                                            <span>{{ $option->option_text }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            @elseif ($question->type === 'multiple_choice')
                                <div class="mt-3 space-y-2">
                                    @foreach ($question->options as $option)
                                        <label class="flex cursor-pointer items-center gap-3 rounded-2xl border border-slate-200 bg-white px-3 py-2.5 text-xs font-medium text-ink transition-colors hover:border-accent-lavender">
                                            <input type="checkbox"
                                                   name="answers[{{ $question->id }}][option_ids][]"
                                                   value="{{ $option->id }}"
                                                   @checked(in_array((string) $option->id, array_map('strval', (array) old("answers.{$question->id}.option_ids", [])), true))
                                                   class="h-4 w-4 rounded border-slate-300 text-accent-lavender focus:ring-accent-lavender">
                                            <span>{{ $option->option_text }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            @elseif ($question->type === 'rating')
                                <div class="mt-3 flex flex-wrap gap-1.5">
                                    @foreach (range(1, 5) as $score)
                                        <label class="flex cursor-pointer items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-ink-muted transition-colors hover:border-accent-lavender">
                                            <input type="radio"
                                                   name="answers[{{ $question->id }}][rating]"
                                                   value="{{ $score }}"
                                                   @checked((string) old("answers.{$question->id}.rating") === (string) $score)
                                                   class="h-4 w-4 border-slate-300 text-accent-lavender focus:ring-accent-lavender">
                                            <span>{{ $score }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            @elseif ($question->type === 'short_text')
                                <div class="mt-3">
                                    <input type="text"
                                           name="answers[{{ $question->id }}][text]"
                                           value="{{ old("answers.{$question->id}.text") }}"
                                           maxlength="255"
                                           placeholder="Tulis jawaban singkat"
                                           class="w-full rounded-2xl border border-slate-200 bg-white px-3 py-2.5 text-xs font-medium text-ink placeholder:text-ink-muted focus:border-accent-lavender focus:outline-none focus:ring-2 focus:ring-accent-lavender/30">
                                    <p class="mt-1 text-[11px] font-medium text-ink-muted">Maksimal 255 karakter.</p>
                                </div>
                            @elseif ($question->type === 'long_text')
                                <div class="mt-3">
                                    <textarea name="answers[{{ $question->id }}][text]"
                                              rows="4"
                                              maxlength="5000"
                                              placeholder="Tulis jawaban lengkap"
                                              class="w-full rounded-2xl border border-slate-200 bg-white px-3 py-2.5 text-xs font-medium text-ink placeholder:text-ink-muted focus:border-accent-lavender focus:outline-none focus:ring-2 focus:ring-accent-lavender/30">{{ old("answers.{$question->id}.text") }}</textarea>
                                    <p class="mt-1 text-[11px] font-medium text-ink-muted">Maksimal 5000 karakter.</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </article>
            @empty
                <div class="rounded-3xl border border-white bg-white/90 px-5 py-12 text-center text-xs font-medium text-ink-muted shadow-[0_10px_30px_-12px_rgba(150,165,205,0.4)]">
                    Survei ini belum memiliki pertanyaan.
                </div>
            @endforelse

            <div class="rounded-3xl border border-white bg-white/90 p-5 shadow-[0_10px_30px_-12px_rgba(150,165,205,0.4)]">
                <button type="submit"
                        class="w-full rounded-2xl bg-accent-lavender px-4 py-2.5 text-xs font-bold text-white shadow-[0_8px_20px_-6px_rgba(155,153,233,0.5)] transition-colors hover:bg-accent-lavender-soft sm:w-auto sm:px-8">
                    Kirim Jawaban
                </button>
                <p class="mt-2 text-[11px] font-medium text-ink-muted">Jawaban yang sudah dikirim tidak dapat diubah.</p>
            </div>
        </form>
    </main>
@endsection
