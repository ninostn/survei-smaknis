@extends(auth()->user()->role === 'admin' ? 'layouts.admin' : 'layouts.app')

@section('title', 'Kelola Survei')
@section('admin-main-width', 'max-w-7xl')
@section('admin-subtitle', 'Daftar survei sekolah dan status pengisian responden.')

@section(auth()->user()->role === 'admin' ? 'admin-content' : 'content')
    @if (auth()->user()->role === 'admin')
        @if (session('status'))
            <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-600" role="alert">
                <ul class="space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="space-y-5">
            {{-- FILTER & VIEW CONTROLS BAR --}}
            <form method="GET" action="{{ route('surveys.index') }}"
                  class="flex flex-col items-center justify-between gap-3 rounded-xl border border-surface-container bg-surface-container-lowest p-4 shadow-sm md:flex-row">
                <div class="relative w-full md:w-96">
                    <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-[20px] text-outline">search</span>
                    <input type="text" name="search" value="{{ e($search) }}" placeholder="Cari survei atau kuesioner..."
                           class="w-full rounded-lg border border-outline-variant/50 bg-surface py-2 pl-10 pr-4 text-body-md font-body-md text-on-surface transition-all placeholder:text-outline focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20">
                </div>

                <div class="flex w-full flex-wrap items-center justify-end gap-2 md:w-auto">
                    <x-filter-modal title="Filter Survei" :reset-url="route('surveys.index')"
                                    :active-count="($categoryId ? 1 : 0) + ($status ? 1 : 0)">
                        <div class="flex flex-col gap-1.5">
                            <label for="survey-filter-category" class="text-xs font-semibold text-slate-700">Kategori Survei</label>
                            <div class="relative">
                                <select id="survey-filter-category" name="category_id"
                                        class="w-full h-10 cursor-pointer appearance-none rounded-xl border border-slate-200 bg-slate-50 pl-3.5 pr-8 text-sm font-medium text-slate-800 focus:border-blue-500 focus:outline-none">
                                    <option value="">Semua Kategori</option>
                                    @foreach ($categories as $category)
                                        <option value="{{ $category->id }}" @selected($categoryId === $category->id)>{{ $category->name }}</option>
                                    @endforeach
                                </select>
                                <span class="material-symbols-outlined pointer-events-none absolute right-2.5 top-2.5 text-slate-400 text-base">expand_more</span>
                            </div>
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <label for="survey-filter-status" class="text-xs font-semibold text-slate-700">Status Survei</label>
                            <div class="relative">
                                <select id="survey-filter-status" name="status"
                                        class="w-full h-10 cursor-pointer appearance-none rounded-xl border border-slate-200 bg-slate-50 pl-3.5 pr-8 text-sm font-medium text-slate-800 focus:border-blue-500 focus:outline-none">
                                    <option value="">Semua Status</option>
                                    <option value="published" @selected($status === 'published')>Aktif</option>
                                    <option value="closed" @selected($status === 'closed')>Ditutup</option>
                                    <option value="draft" @selected($status === 'draft')>Draft</option>
                                </select>
                                <span class="material-symbols-outlined pointer-events-none absolute right-2.5 top-2.5 text-slate-400 text-base">expand_more</span>
                            </div>
                        </div>
                    </x-filter-modal>

                    <a href="{{ route('surveys.index') }}"
                       class="inline-flex items-center gap-1.5 rounded-xl border border-outline-variant/60 px-3 py-2 text-label-md font-label-md text-on-surface transition-colors hover:bg-surface-container-low">
                        <span>Reset</span>
                    </a>
                    <x-admin-add-link :href="route('surveys.create')" label="Survei Baru" />
                </div>
            </form>

            {{-- Responsive card grid --}}
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                @forelse ($surveys as $survey)
                    @php
                        $canManage = auth()->user()?->role === 'admin' || $survey->creator_id === auth()->id();

                        [$statusLabel, $statusClass] = match ($survey->display_status) {
                            'Berlangsung' => ['Aktif', 'bg-emerald-50 text-emerald-700 border border-emerald-200'],
                            'Dijadwalkan' => ['Dijadwalkan', 'bg-sky-50 text-sky-700 border border-sky-200'],
                            'Ditutup', 'Berakhir' => [$survey->display_status, 'bg-red-50 text-red-600 border border-red-200'],
                            default => ['Draft', 'bg-slate-100 text-slate-600 border border-slate-200'],
                        };

                        $categoryChipClass = $survey->category && $survey->status !== 'closed'
                            ? 'bg-surface-container-low text-primary border border-surface-container'
                            : 'bg-surface-container text-on-surface-variant border border-surface-container';

                        $targetParts = [];
                        if ($survey->student_target_mode === 'all') {
                            $targetParts[] = 'Seluruh Siswa';
                        } elseif ($survey->student_target_mode === 'classes') {
                            $targetParts[] = 'Siswa '.$survey->targetClasses->pluck('name')->implode(', ');
                        }
                        if ($survey->teacher_target_mode === 'all') {
                            $targetParts[] = 'Seluruh Guru';
                        } elseif ($survey->teacher_target_mode === 'selected') {
                            $targetParts[] = 'Guru Terpilih';
                        }
                        $targetLabel = count($targetParts) > 0 ? implode(' & ', $targetParts) : '—';

                        $targetCount = \App\Support\AdminDashboardStats::surveyTarget($survey);
                        $responses = (int) $survey->responses_count;
                        $percentage = $targetCount > 0 ? (int) round($responses / $targetCount * 100) : 0;
                        $isComplete = $targetCount > 0 && $responses >= $targetCount;
                        $isDraft = $survey->status === 'draft';

                        $unit = match (true) {
                            $survey->student_target_mode !== 'none' => 'siswa',
                            $survey->teacher_target_mode !== 'none' => 'guru',
                            default => 'responden',
                        };
                        $responsLabel = match (true) {
                            $survey->student_target_mode !== 'none' => 'Respons Siswa',
                            $survey->teacher_target_mode !== 'none' => 'Respons Guru',
                            default => 'Respons',
                        };

                        $barClass = $isComplete ? 'bg-emerald-600' : ($percentage === 0 ? 'bg-outline' : 'bg-primary-container');

                        $monthAbbr = [1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
                        $formatDate = fn ($date) => $date->format('d').' '.$monthAbbr[(int) $date->format('n')];

                        if ($survey->starts_at && $survey->ends_at && ! $isDraft) {
                            $periode = $formatDate($survey->starts_at).' - '.$formatDate($survey->ends_at).' '.$survey->ends_at->year;
                            $periodeDone = $survey->status === 'closed' || now()->gt($survey->ends_at);
                        } else {
                            $periode = 'Belum dimulai';
                            $periodeDone = false;
                        }

                        $deleteConfirmation = $responses > 0
                            ? "Survei ini memiliki {$responses} respons. Menghapus survei juga akan menghapus seluruh respons dan jawaban terkait. Tindakan ini tidak dapat dibatalkan."
                            : 'Hapus survei ini? Tindakan ini tidak dapat dibatalkan.';
                    @endphp

                    <article class="flex min-w-0 flex-col justify-between rounded-xl border border-surface-container bg-surface-container-lowest p-3.5 shadow-sm transition-shadow duration-200 hover:shadow-md">
                        <div>
                            {{-- Top Row: Badge & Status --}}
                            <div class="mb-2 flex items-center justify-between gap-1.5">
                                <span class="min-w-0 rounded-full border px-2.5 py-1 text-label-sm font-label-sm wrap-break-word {{ $categoryChipClass }}">
                                    {{ $survey->category?->name ?? 'Tanpa Kategori' }}
                                </span>
                                <span class="inline-flex shrink-0 items-center gap-1.5 rounded-full px-2.5 py-1 text-label-sm font-label-sm {{ $statusClass }}">
                                    {{ $statusLabel }}
                                </span>
                            </div>

                            {{-- Title --}}
                            <h2 class="line-clamp-2 text-sm font-bold leading-snug text-on-surface">{{ $survey->title }}</h2>

                            {{-- Metadata List --}}
                            <div class="mt-2.5 space-y-1 text-body-sm font-body-sm text-on-surface-variant">
                                <div class="flex items-center gap-2">
                                    <span aria-hidden="true" class="material-symbols-outlined shrink-0 text-[18px] text-outline">groups</span>
                                    <span>Target: <strong class="font-semibold text-on-surface">{{ $targetLabel }}</strong></span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span aria-hidden="true" class="material-symbols-outlined shrink-0 text-[18px] text-outline">event</span>
                                    <span>Periode: {{ $periode }} @if ($periodeDone)<span class="text-outline">(Selesai)</span>@endif</span>
                                </div>
                            </div>

                            {{-- Progress Bar --}}
                            <div class="mt-3 border-t border-surface-container pt-2">
                                <div class="mb-1.5 flex flex-wrap items-center justify-between gap-x-2 text-body-sm font-body-sm">
                                    <span class="text-outline">{{ $responsLabel }}</span>
                                    @if ($isComplete)
                                        <span class="font-semibold text-emerald-700">{{ $responses }} / {{ $targetCount }} {{ $unit }} (100% komplit)</span>
                                    @elseif ($isDraft || $targetCount === 0)
                                        <span class="font-semibold text-outline">{{ $responses }} / {{ $targetCount }} {{ $unit }} ({{ $percentage }}%)</span>
                                    @else
                                        <span class="font-semibold text-on-surface">{{ $responses }} / {{ $targetCount }} {{ $unit }} <span class="text-primary">({{ $percentage }}%)</span></span>
                                    @endif
                                </div>
                                <div class="h-1.5 w-full overflow-hidden rounded-full bg-surface-container-low">
                                    <div class="h-full rounded-full {{ $barClass }}" style="width: {{ min(100, $percentage) }}%"></div>
                                </div>
                            </div>
                        </div>

                        {{-- Bottom Actions --}}
                        <div class="mt-3 flex flex-wrap items-center justify-between gap-1.5 border-t border-surface-container pt-2">
                            @if ($survey->status === 'draft' && $canManage)
                                <a href="{{ route('surveys.publish.create', $survey) }}" data-publish-open
                                   class="inline-flex items-center gap-1.5 rounded-xl bg-primary-container px-3.5 py-2 font-label-lg text-label-lg text-on-primary transition-colors hover:bg-primary">
                                    <span aria-hidden="true" class="material-symbols-outlined text-[18px]">publish</span>
                                    <span>Publikasikan</span>
                                </a>
                                <div class="flex flex-wrap items-center gap-1">
                                    <a href="{{ route('surveys.edit', $survey) }}" title="Edit Survei"
                                       class="rounded-lg p-2 text-outline transition-colors hover:bg-surface-container-low hover:text-on-surface">
                                        <span class="material-symbols-outlined text-[18px]">edit</span>
                                    </a>
                                    <form method="POST"
                                          action="{{ route('surveys.destroy', $survey) }}"
                                          onsubmit="return confirm(@js($deleteConfirmation));">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" title="Hapus"
                                                class="rounded-lg p-2 text-outline transition-colors hover:bg-error-container/30 hover:text-error">
                                            <span class="material-symbols-outlined text-[18px]">delete</span>
                                        </button>
                                    </form>
                                </div>
                            @elseif ($survey->status === 'published' && $canManage)
                                <a href="{{ route('surveys.results.show', $survey) }}"
                                   class="inline-flex items-center gap-1.5 rounded-xl bg-surface-container-low px-3.5 py-2 font-label-lg text-label-lg text-primary transition-colors hover:bg-surface-container">
                                    <span aria-hidden="true" class="material-symbols-outlined text-[18px]">insights</span>
                                    <span>Lihat Hasil</span>
                                </a>
                                <div class="flex items-center gap-1">
                                    <form method="POST"
                                          action="{{ route('surveys.close', $survey) }}"
                                          onsubmit="return confirm('Tutup survei ini? Survei yang ditutup tidak dapat dibuka kembali.');">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" title="Tutup Survei"
                                                class="rounded-lg p-2 text-outline transition-colors hover:bg-surface-container-low hover:text-on-surface">
                                            <span class="material-symbols-outlined text-[18px]">lock</span>
                                        </button>
                                    </form>
                                </div>
                            @elseif ($canManage)
                                <a href="{{ route('surveys.results.show', $survey) }}"
                                   class="inline-flex items-center gap-1.5 rounded-xl bg-surface-container px-3.5 py-2 font-label-lg text-label-lg text-on-surface transition-colors hover:bg-surface-container-high">
                                    <span aria-hidden="true" class="material-symbols-outlined text-[18px]">assessment</span>
                                    <span>Lihat Laporan</span>
                                </a>
                            @else
                                <span class="text-outline">&mdash;</span>
                            @endif
                        </div>
                    </article>
                @empty
                    <div class="col-span-full rounded-2xl border border-dashed border-outline-variant/60 bg-surface-container-lowest p-12 text-center text-body-md text-on-surface-variant">
                        Belum ada survei yang cocok.
                    </div>
                @endforelse
            </div>

            <div>
                {{ $surveys->links() }}
            </div>
        </div>
    @else
        @include('surveys._nav')

        <main class="mx-auto w-full max-w-6xl px-4 py-8">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-ink">Kelola Survei</h1>
                    <p class="mt-1 text-xs font-medium text-ink-muted">Kelola survei yang Anda buat atau seluruh survei sekolah.</p>
                </div>
                <a href="{{ route('surveys.create') }}"
                   class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-brand-700">
                    <x-icon name="plus" class="h-4 w-4"/>
                    Tambah Survei
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

            <form method="GET" action="{{ route('surveys.index') }}" class="mt-5 grid grid-cols-1 gap-3 rounded-2xl border border-slate-200 bg-white p-4 sm:grid-cols-2 lg:grid-cols-4">
                <input type="search" name="search" value="{{ e($search) }}" placeholder="Cari judul survei..."
                       class="rounded-xl border border-slate-200 bg-slate-50/60 px-4 py-2.5 text-xs focus:border-brand-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-500/20">

                <select name="status" class="rounded-xl border border-slate-200 bg-slate-50/60 px-4 py-2.5 text-xs focus:border-brand-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-500/20">
                    <option value="">Semua status</option>
                    <option value="draft" @selected($status === 'draft')>Draft</option>
                    <option value="published" @selected($status === 'published')>Dipublikasikan</option>
                    <option value="closed" @selected($status === 'closed')>Ditutup</option>
                </select>

                <select name="category_id" class="rounded-xl border border-slate-200 bg-slate-50/60 px-4 py-2.5 text-xs focus:border-brand-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-500/20">
                    <option value="">Semua kategori</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected($categoryId === $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>

                <div class="flex gap-2">
                    <button type="submit" class="flex-1 rounded-xl bg-brand-600 px-4 py-2.5 text-xs font-semibold text-white transition-colors hover:bg-brand-700">
                        Terapkan
                    </button>
                    <a href="{{ route('surveys.index') }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-semibold text-slate-600 transition-colors hover:text-ink">
                        Reset
                    </a>
                </div>
            </form>

            <div class="mt-6 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                @forelse ($surveys as $survey)
                    @php
                        $canManage = auth()->user()?->role === 'admin' || $survey->creator_id === auth()->id();

                        [$badgeBg, $badgeText] = match ($survey->display_status) {
                            'Berlangsung' => ['bg-brand-50', 'text-brand-700'],
                            'Dijadwalkan' => ['bg-sky-50', 'text-sky-700'],
                            'Ditutup', 'Berakhir' => ['bg-red-50', 'text-red-600'],
                            default => ['bg-slate-100', 'text-slate-600'], // Draft
                        };

                        $periode = '&mdash;';
                        if ($survey->status !== 'draft' && $survey->starts_at && $survey->ends_at) {
                            $formatDate = fn ($date) => $date->day.' '.$date->translatedFormat('M');

                            $periode = $survey->status === 'published' && now()->between($survey->starts_at, $survey->ends_at)
                                ? 'Berakhir '.$formatDate($survey->ends_at)
                                : $formatDate($survey->starts_at).' &ndash; '.$formatDate($survey->ends_at);
                        }
                    @endphp

                    <article class="flex flex-col rounded-2xl border border-slate-200 bg-white p-5 transition-shadow hover:shadow-[0_10px_30px_-14px_rgba(37,99,235,0.25)]">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <span class="inline-flex items-center rounded-full border border-slate-200 bg-slate-50 px-2.5 py-1 text-[11px] font-semibold text-brand-600">
                                {{ $survey->category?->name ?? 'Tanpa Kategori' }}
                            </span>
                            <span class="inline-flex rounded-full {{ $badgeBg }} px-2.5 py-1 text-[11px] font-bold {{ $badgeText }}">
                                {{ $survey->display_status }}
                            </span>
                        </div>

                        <h2 class="mt-3 text-base font-bold leading-snug text-slate-900">{{ $survey->title }}</h2>

                        <dl class="mt-4 space-y-2.5 text-sm text-slate-600">
                            <div class="flex items-center gap-2">
                                <x-icon name="users" class="h-3.5 w-3.5 shrink-0 text-slate-400"/>
                                <dd class="font-semibold tabular-nums text-slate-700">{{ (int) $survey->responses_count }} respons</dd>
                            </div>
                            <div class="flex items-center gap-2">
                                <x-icon name="calendar" class="h-3.5 w-3.5 shrink-0 text-slate-400"/>
                                <dd>{!! $periode !!}</dd>
                            </div>
                        </dl>

                        <div class="mt-6 flex flex-wrap items-center gap-2 border-t border-slate-100 pt-4">
                            @if ($canManage)
                                <a href="{{ route('surveys.results.show', $survey) }}"
                                   class="inline-flex items-center gap-1.5 rounded-full border border-brand-200 bg-brand-50 px-3 py-1.5 text-[11px] font-bold text-brand-700 transition-colors hover:border-brand-300 hover:bg-brand-100">
                                    <x-icon name="chart" class="h-3.5 w-3.5"/>
                                    Lihat Hasil
                                </a>
                            @endif

                            @if ($survey->status === 'draft' && $canManage)
                                <a href="{{ route('surveys.questions.index', $survey) }}"
                                   class="inline-flex rounded-full border border-slate-200 bg-white px-3 py-1.5 text-[11px] font-semibold text-slate-600 transition-colors hover:border-slate-300 hover:text-ink">
                                    Pertanyaan
                                </a>
                                <a href="{{ route('surveys.edit', $survey) }}"
                                   class="inline-flex rounded-full border border-slate-200 bg-white px-3 py-1.5 text-[11px] font-semibold text-slate-600 transition-colors hover:border-slate-300 hover:text-ink">
                                    Edit
                                </a>
                                <a href="{{ route('surveys.publish.create', $survey) }}" data-publish-open
                                   class="inline-flex rounded-full border border-emerald-200 bg-white px-3 py-1.5 text-[11px] font-semibold text-emerald-600 transition-colors hover:bg-emerald-50">
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
                                            class="inline-flex rounded-full border border-rose-200 bg-white px-3 py-1.5 text-[11px] font-semibold text-rose-600 transition-colors hover:bg-rose-50">
                                        Hapus
                                    </button>
                                </form>
                            @elseif ($survey->status === 'published' && $canManage)
                                <form method="POST"
                                      action="{{ route('surveys.close', $survey) }}"
                                      onsubmit="return confirm('Tutup survei ini? Survei yang ditutup tidak dapat dibuka kembali.');">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit"
                                            class="inline-flex rounded-full border border-rose-200 bg-white px-3 py-1.5 text-[11px] font-semibold text-rose-600 transition-colors hover:bg-rose-50">
                                        Tutup
                                    </button>
                                </form>
                            @elseif (! $canManage)
                                <span class="text-slate-400">&mdash;</span>
                            @endif
                        </div>
                    </article>
                @empty
                    <div class="rounded-2xl border border-dashed border-slate-200 bg-white px-6 py-12 text-center text-sm text-slate-500 md:col-span-2 xl:col-span-3">
                        Belum ada survei yang cocok.
                    </div>
                @endforelse
            </div>

            <div class="mt-6">
                {{ $surveys->links() }}
            </div>
        </main>
    @endif
    <dialog id="publish-dialog" aria-label="Publikasikan survei" class="m-auto max-h-[90vh] w-[min(92vw,36rem)] overflow-auto rounded-2xl bg-white p-0 text-slate-900 shadow-xl backdrop:bg-slate-900/60">
        <div class="flex items-center justify-between border-b border-slate-200 px-6 py-3">
            <span class="text-sm font-semibold">Publikasikan survei</span>
            <button type="button" data-publish-close aria-label="Tutup" class="rounded-lg p-2 text-slate-500 hover:bg-slate-100"><span aria-hidden="true" class="material-symbols-outlined text-xl">close</span></button>
        </div>
        <div data-publish-body class="px-6 pb-6" aria-live="polite"></div>
    </dialog>
@endsection
