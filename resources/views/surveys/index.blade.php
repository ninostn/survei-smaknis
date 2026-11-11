@extends('layouts.app')

@section('title', 'Manajemen Survei')

@section('content')
    @include('surveys._nav')

    <main class="mx-auto w-full max-w-6xl px-4 py-8">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-ink-muted">Manajemen Survei</p>
                <h1 class="mt-1 text-2xl font-bold tracking-tight text-ink">Survei</h1>
                <p class="mt-1 text-xs font-medium text-ink-muted">Kelola survei yang Anda buat atau seluruh survei sekolah.</p>
            </div>
            <a href="{{ route('surveys.create') }}"
               class="inline-flex items-center justify-center rounded-2xl bg-accent-lavender px-4 py-2.5 text-xs font-bold text-white shadow-[0_8px_20px_-6px_rgba(155,153,233,0.5)] transition-colors hover:bg-accent-lavender-soft">
                + Tambah Survei
            </a>
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

        <form method="GET" action="{{ route('surveys.index') }}" class="mt-5 grid grid-cols-1 gap-3 rounded-3xl border border-white bg-white/90 p-4 shadow-[0_10px_30px_-12px_rgba(150,165,205,0.4)] sm:grid-cols-2 lg:grid-cols-4">
            <input type="search" name="search" value="{{ e($search) }}" placeholder="Cari judul survei..."
                   class="rounded-2xl border border-slate-200 bg-slate-50/60 px-4 py-2.5 text-xs focus:border-accent-lavender focus:bg-white focus:outline-none focus:ring-2 focus:ring-accent-lavender/30">

            <select name="status" class="rounded-2xl border border-slate-200 bg-slate-50/60 px-4 py-2.5 text-xs focus:border-accent-lavender focus:bg-white focus:outline-none focus:ring-2 focus:ring-accent-lavender/30">
                <option value="">Semua status</option>
                <option value="draft" @selected($status === 'draft')>Draft</option>
                <option value="published" @selected($status === 'published')>Dipublikasikan</option>
                <option value="closed" @selected($status === 'closed')>Ditutup</option>
            </select>

            <select name="category_id" class="rounded-2xl border border-slate-200 bg-slate-50/60 px-4 py-2.5 text-xs focus:border-accent-lavender focus:bg-white focus:outline-none focus:ring-2 focus:ring-accent-lavender/30">
                <option value="">Semua kategori</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @selected($categoryId === $category->id)>{{ $category->name }}</option>
                @endforeach
            </select>

            <div class="flex gap-2">
                <button type="submit" class="flex-1 rounded-2xl bg-ink px-4 py-2.5 text-xs font-bold text-white transition-colors hover:bg-slate-700">
                    Terapkan
                </button>
                <a href="{{ route('surveys.index') }}" class="rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-semibold text-ink-muted transition-colors hover:text-ink">
                    Reset
                </a>
            </div>
        </form>

        <div class="mt-5 overflow-hidden rounded-3xl border border-white bg-white/90 shadow-[0_10px_30px_-12px_rgba(150,165,205,0.4)]">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[820px] text-left text-xs">
                    <thead>
                        <tr class="border-b border-slate-100 text-[11px] uppercase tracking-wider text-ink-muted">
                            <th class="px-5 py-4 font-semibold">Judul</th>
                            <th class="px-5 py-4 font-semibold">Kategori</th>
                            <th class="px-5 py-4 font-semibold">Pembuat</th>
                            <th class="px-5 py-4 font-semibold">Status</th>
                            <th class="px-5 py-4 font-semibold">Dibuat</th>
                            <th class="px-5 py-4 font-semibold">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($surveys as $survey)
                            <tr class="border-b border-slate-50 last:border-0">
                                <td class="px-5 py-4 font-bold text-ink">{{ $survey->title }}</td>
                                <td class="px-5 py-4 font-medium text-ink-muted">{{ $survey->category?->name ?? '—' }}</td>
                                <td class="px-5 py-4 font-medium text-ink-muted">{{ $survey->creator?->username ?? '—' }}</td>
                                <td class="px-5 py-4">
                                    <span @class([
                                        'inline-block rounded-full px-2.5 py-1 text-[11px] font-bold',
                                        'bg-slate-100 text-slate-600' => $survey->status === 'draft',
                                        'bg-emerald-50 text-emerald-600' => $survey->status === 'published',
                                        'bg-rose-50 text-rose-600' => $survey->status === 'closed',
                                    ])>
                                        {{ $survey->display_status }}
                                    </span>
                                    @if ($survey->status === 'published')
                                        <span class="mt-1 block text-[11px] font-medium text-ink-muted">
                                            {{ $survey->starts_at?->format('d/m/Y H:i') ?? '-' }}
                                            &ndash;
                                            {{ $survey->ends_at?->format('d/m/Y H:i') ?? '-' }}
                                        </span>
                                    @endif
                                </td>
                                <td class="px-5 py-4 font-medium text-ink-muted">{{ $survey->created_at?->format('d/m/Y H:i') ?? '—' }}</td>
                                <td class="px-5 py-4">
                                    @php
                                        $canManage = auth()->user()?->role === 'admin' || $survey->creator_id === auth()->id();
                                    @endphp

                                    @if ($survey->status === 'draft' && $canManage)
                                        <div class="flex flex-wrap items-center gap-2">
                                            <a href="{{ route('surveys.questions.index', $survey) }}"
                                               class="inline-flex rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-[11px] font-semibold text-ink-muted transition-colors hover:border-accent-lavender hover:text-ink">
                                                Pertanyaan
                                            </a>
                                            <a href="{{ route('surveys.edit', $survey) }}"
                                               class="inline-flex rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-[11px] font-semibold text-ink-muted transition-colors hover:border-accent-lavender hover:text-ink">
                                                Edit
                                            </a>
                                            <a href="{{ route('surveys.publish.create', $survey) }}"
                                               class="inline-flex rounded-xl border border-emerald-200 bg-white px-3 py-1.5 text-[11px] font-semibold text-emerald-600 transition-colors hover:bg-emerald-50">
                                                Publish
                                            </a>
                                            @php
                                                $responsesCount = (int) $survey->responses_count;
                                                $deleteConfirmation = $responsesCount > 0
                                                    ? "Survei ini memiliki {$responsesCount} respons. Menghapus survei juga akan menghapus seluruh respons dan jawaban terkait. Tindakan ini tidak dapat dibatalkan."
                                                    : 'Hapus survei ini? Tindakan ini tidak dapat dibatalkan.';
                                            @endphp
                                            <form method="POST"
                                                  action="{{ route('surveys.destroy', $survey) }}"
                                                  onsubmit="return confirm(@js($deleteConfirmation));">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                        class="inline-flex rounded-xl border border-rose-200 bg-white px-3 py-1.5 text-[11px] font-semibold text-rose-600 transition-colors hover:bg-rose-50">
                                                    Hapus
                                                </button>
                                            </form>
                                        </div>
                                    @elseif ($survey->status === 'published' && $canManage)
                                        <form method="POST"
                                              action="{{ route('surveys.close', $survey) }}"
                                              onsubmit="return confirm('Tutup survei ini? Survei yang ditutup tidak dapat dibuka kembali.');">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit"
                                                    class="inline-flex rounded-xl border border-rose-200 bg-white px-3 py-1.5 text-[11px] font-semibold text-rose-600 transition-colors hover:bg-rose-50">
                                                Close
                                            </button>
                                        </form>
                                    @else
                                        <span class="text-ink-muted">&mdash;</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-12 text-center text-ink-muted">Belum ada survei yang cocok.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="border-t border-slate-100 px-5 py-3">
                {{ $surveys->links() }}
            </div>
        </div>
    </main>
@endsection
