@extends('layouts.app')

@section('title', 'Survei Tersedia')

@section('content')
    @include('surveys._nav')

    <main class="mx-auto w-full max-w-3xl px-4 py-8">
        <p class="text-xs font-semibold uppercase tracking-wider text-ink-muted">Isi Survei</p>
        <h1 class="mt-1 text-2xl font-bold tracking-tight text-ink">Survei Tersedia</h1>
        <p class="mt-1 text-xs font-medium text-ink-muted">Survei yang dapat Anda isi saat ini.</p>

        @if (session('status'))
            <div class="mt-5 rounded-2xl border border-emerald-100 bg-emerald-50 px-4 py-3 text-xs font-semibold text-emerald-700">
                {{ session('status') }}
            </div>
        @endif

        @if (session('error'))
            <div class="mt-5 rounded-2xl border border-amber-100 bg-amber-50 px-4 py-3 text-xs font-semibold text-amber-700" role="alert">
                {{ session('error') }}
            </div>
        @endif

        <div class="mt-6 space-y-3">
            @forelse ($surveys as $survey)
                <article class="rounded-3xl border border-white bg-white/90 p-5 shadow-[0_10px_30px_-12px_rgba(150,165,205,0.4)]">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-bold text-ink-muted">
                                    {{ $survey->category?->name ?? 'Tanpa kategori' }}
                                </span>
                                <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-[11px] font-bold text-emerald-600">
                                    {{ $survey->display_status }}
                                </span>
                            </div>

                            <h2 class="mt-2 text-sm font-bold text-ink">{{ $survey->title }}</h2>

                            @if ($survey->description)
                                <p class="mt-1 line-clamp-2 text-xs font-medium text-ink-muted">{{ $survey->description }}</p>
                            @endif

                            <dl class="mt-3 flex flex-wrap gap-x-4 gap-y-1 text-[11px] font-medium text-ink-muted">
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
                            </dl>
                        </div>

                        <a href="{{ route('responses.show', $survey) }}"
                           class="inline-flex shrink-0 items-center justify-center rounded-2xl bg-accent-lavender px-4 py-2.5 text-xs font-bold text-white shadow-[0_8px_20px_-6px_rgba(155,153,233,0.5)] transition-colors hover:bg-accent-lavender-soft">
                            Lihat Survei
                        </a>
                    </div>
                </article>
            @empty
                <div class="rounded-3xl border border-white bg-white/90 p-8 text-center shadow-[0_10px_30px_-12px_rgba(150,165,205,0.4)]">
                    <p class="text-sm font-bold text-ink">Belum ada survei yang bisa diisi.</p>
                    <p class="mt-1 text-xs font-medium text-ink-muted">Survei akan muncul di sini ketika sudah dipublikasikan dan waktunya tiba.</p>
                </div>
            @endforelse
        </div>

        @if ($surveys->hasPages())
            <div class="mt-5">
                {{ $surveys->links() }}
            </div>
        @endif
    </main>
@endsection
