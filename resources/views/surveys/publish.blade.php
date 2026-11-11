@extends('layouts.app')

@section('title', 'Publikasikan Survei')

@section('content')
    @include('surveys._nav')

    <main class="mx-auto w-full max-w-2xl px-4 py-8">
        <a href="{{ route('surveys.index') }}" class="text-xs font-semibold text-ink-muted hover:text-ink">&larr; Kembali ke daftar survei</a>

        <div class="mt-3 rounded-3xl border border-white bg-white/90 p-6 shadow-[0_10px_30px_-12px_rgba(150,165,205,0.4)] sm:p-8">
            <p class="text-xs font-semibold uppercase tracking-wider text-ink-muted">Manajemen Survei</p>
            <h1 class="mt-1 text-xl font-bold tracking-tight text-ink">Publikasikan Survei</h1>
            <p class="mt-1 text-xs font-medium text-ink-muted">Survei: {{ $survey->title }}</p>

            @if ($errors->any())
                <div class="mt-5 rounded-2xl border border-red-100 bg-red-50 px-4 py-3 text-xs font-medium text-red-600" role="alert">
                    <ul class="space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if ($issues !== [])
                <div class="mt-5 rounded-2xl border border-amber-100 bg-amber-50 px-4 py-3 text-xs font-medium text-amber-700" role="alert">
                    <p class="font-semibold">Survei belum dapat dipublikasikan:</p>
                    <ul class="mt-1 space-y-1">
                        @foreach ($issues as $issue)
                            <li>{{ $issue }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST"
                  action="{{ route('surveys.publish', $survey) }}"
                  class="mt-6 space-y-5"
                  onsubmit="return confirm('Publikasikan survei ini? Setelah dipublikasikan, pertanyaan dan target tidak dapat diubah.');">
                @csrf
                @method('PATCH')

                <label class="block">
                    <span class="mb-1.5 block text-xs font-semibold text-ink-muted">Waktu mulai</span>
                    <input type="datetime-local" name="starts_at" required
                           value="{{ old('starts_at', $survey->starts_at?->format('Y-m-d\TH:i') ?? now()->format('Y-m-d\TH:i')) }}"
                           class="w-full rounded-2xl border border-slate-200 bg-slate-50/60 px-4 py-2.5 text-sm focus:border-accent-lavender focus:bg-white focus:outline-none focus:ring-2 focus:ring-accent-lavender/30">
                </label>

                <label class="block">
                    <span class="mb-1.5 block text-xs font-semibold text-ink-muted">Waktu berakhir</span>
                    <input type="datetime-local" name="ends_at" required
                           value="{{ old('ends_at', $survey->ends_at?->format('Y-m-d\TH:i') ?? now()->addDays(7)->format('Y-m-d\TH:i')) }}"
                           class="w-full rounded-2xl border border-slate-200 bg-slate-50/60 px-4 py-2.5 text-sm focus:border-accent-lavender focus:bg-white focus:outline-none focus:ring-2 focus:ring-accent-lavender/30">
                </label>

                <div class="flex flex-wrap items-center justify-end gap-2 pt-1">
                    <a href="{{ route('surveys.index') }}"
                       class="rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-semibold text-ink-muted transition-colors hover:text-ink">
                        Batal
                    </a>
                    <button type="submit"
                            class="rounded-2xl bg-ink px-4 py-2.5 text-xs font-bold text-white transition-colors hover:bg-slate-700">
                        Publikasikan
                    </button>
                </div>
            </form>
        </div>
    </main>
@endsection
