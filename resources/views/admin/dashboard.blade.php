@extends('layouts.admin')

@section('title', 'Dashboard Admin')
@section('admin-heading', 'Dashboard')
@section('admin-subtitle', 'Ringkasan survei aktif dan partisipasi responden.')


@section('admin-content')
    @php
        $activeSub = match (true) {
            $activeCount === 0 => 'Tidak ada survei yang berlangsung',
            $endingThisWeek > 0 => $endingThisWeek.' di antaranya berakhir minggu ini',
            default => 'Semua survei berjalan normal',
        };

        $kpis = [
            ['label' => 'Survei Aktif', 'icon' => 'poll', 'value' => (string) $activeCount, 'sub' => $activeSub],
            ['label' => 'Total Respons', 'icon' => 'group', 'value' => number_format($totalResponses, 0, ',', '.'), 'sub' => 'Siswa dan guru, semua survei'],
            ['label' => 'Partisipasi', 'icon' => 'how_to_reg', 'value' => $participation === null ? '—' : $participation.'%', 'sub' => 'Rata-rata seluruh survei aktif'],
            ['label' => 'Rata-rata Rating', 'icon' => 'star', 'value' => $avgRating === null ? '—' : str_replace('.', ',', number_format($avgRating, 1)).'/5', 'sub' => 'Dari pertanyaan bertipe rating'],
        ];

        // Skala sumbu Y dibulatkan ke kelipatan 10, 4 tingkat.
        $maxValue = max(1, ...array_merge(
            array_column($trend, 'student'),
            array_column($trend, 'teacher'),
        ));
        $step = (int) ceil(max(1, (int) ceil($maxValue / 4)) / 10) * 10;
        $scale = $step * 4;

        $barHeights = collect($trend)->map(fn (array $month): array => [
            'student' => $month['student'] > 0 ? max(4, (int) round($month['student'] / $scale * 176)) : 0,
            'teacher' => $month['teacher'] > 0 ? max(4, (int) round($month['teacher'] / $scale * 176)) : 0,
        ])->all();
    @endphp

    <section aria-label="Ringkasan" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ($kpis as $index => $kpi)
            <div class="dash-rise rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm" style="animation-delay: {{ 0.05 + $index * 0.05 }}s">
                <div class="flex items-center justify-between">
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">{{ $kpi['label'] }}</p>
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-50 text-blue-600">
                        <span class="material-symbols-outlined text-[18px]">{{ $kpi['icon'] }}</span>
                    </span>
                </div>
                <p class="mt-2 text-3xl font-bold tracking-tight text-slate-900 tabular-nums">{{ $kpi['value'] }}</p>
                <p class="mt-1 text-xs text-slate-600">{{ $kpi['sub'] }}</p>
            </div>
        @endforeach
    </section>

    <section aria-label="Grafik" class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-12">
        <div class="dash-rise flex flex-col rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm lg:col-span-8" style="animation-delay: .25s">
            <div>
                <h2 class="text-base font-bold text-slate-900">Tren Respons Bulanan</h2>
                <p class="mt-0.5 text-xs text-slate-500">Jumlah respons masuk per bulan &middot; 6 bulan terakhir</p>
            </div>

            <div class="mt-5 flex gap-3">
                <div class="flex h-44 w-9 shrink-0 flex-col justify-between text-right text-[10px] font-semibold tabular-nums text-slate-500">
                    <span>{{ $scale }}</span>
                    <span>{{ $scale * 3 / 4 }}</span>
                    <span>{{ $scale / 2 }}</span>
                    <span>{{ $scale / 4 }}</span>
                    <span>0</span>
                </div>
                <div class="min-w-0 flex-1">
                    <div class="relative h-44 border-l border-slate-200">
                        <div class="pointer-events-none absolute inset-0 flex flex-col justify-between">
                            <div class="border-t border-slate-100"></div>
                            <div class="border-t border-slate-100"></div>
                            <div class="border-t border-slate-100"></div>
                            <div class="border-t border-slate-100"></div>
                            <div class="border-t border-slate-200"></div>
                        </div>
                        <div class="relative z-10 flex h-full justify-around">
                            @foreach ($trend as $index => $month)
                                <div class="flex h-full w-11 items-end justify-center">
                                    <div class="flex items-end justify-center gap-1">
                                        <div class="flex flex-col items-center justify-end">
                                            <span class="mb-0.5 text-[10px] font-bold tabular-nums text-blue-700">{{ $month['student'] }}</span>
                                            <div class="w-4 rounded-t bg-blue-600" style="height: {{ $barHeights[$index]['student'] }}px"></div>
                                        </div>
                                        <div class="ml-1 flex flex-col items-center justify-end">
                                            <span class="mb-0.5 text-[10px] font-bold tabular-nums text-sky-700">{{ $month['teacher'] }}</span>
                                            <div class="w-4 rounded-t bg-sky-400" style="height: {{ $barHeights[$index]['teacher'] }}px"></div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="mt-2 flex justify-around">
                        @foreach ($trend as $month)
                            <span class="w-11 text-center text-[10px] font-semibold uppercase text-slate-500">{{ $month['label'] }}</span>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="mt-4 flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 pt-4 text-xs text-slate-600">
                <div class="flex items-center gap-5">
                    <span class="flex items-center gap-2"><span class="h-2.5 w-2.5 rounded-full bg-blue-600"></span>Respons Siswa</span>
                    <span class="flex items-center gap-2"><span class="h-2.5 w-2.5 rounded-full bg-sky-400"></span>Respons Guru</span>
                </div>
                <a href="{{ route('surveys.index') }}" class="inline-flex items-center gap-1.5 font-semibold text-blue-600 transition-colors hover:text-blue-700">
                    Lihat semua survei
                    <span class="material-symbols-outlined text-base">arrow_forward</span>
                </a>
            </div>
        </div>

        <div class="dash-rise flex flex-col rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm lg:col-span-4" style="animation-delay: .3s">
            <div>
                <h2 class="text-base font-bold text-slate-900">Partisipasi per Kategori</h2>
                <p class="mt-0.5 text-xs text-slate-500">Persentase respons per kategori survei</p>
            </div>

            @if (count($categories) === 0)
                <div class="mt-4 flex flex-1 items-center justify-center rounded-xl border border-dashed border-slate-200 p-6 text-center text-xs text-slate-500">
                    Belum ada survei yang dipublikasikan.
                </div>
            @else
                @php
                    $circumference = round(2 * M_PI * 40, 1);
                    $accumulated = 0.0;
                @endphp
                <div class="relative mx-auto my-4 flex h-40 w-40 items-center justify-center">
                    <svg aria-hidden="true" class="h-full w-full -rotate-90" viewBox="0 0 100 100">
                        <circle class="text-slate-100" cx="50" cy="50" fill="transparent" r="40" stroke="currentColor" stroke-width="7"></circle>
                        @foreach ($categories as $category)
                            @continue($category['percentage'] === null)
                            @php
                                $dash = round($category['percentage'] / 100 * $circumference, 1);
                                $offset = round($accumulated, 1);
                                $accumulated += $dash;
                            @endphp
                            <circle cx="50" cy="50" fill="transparent" r="40"
                                    stroke="{{ $category['color'] }}"
                                    stroke-dasharray="{{ $dash }} {{ round($circumference - $dash, 1) }}"
                                    stroke-dashoffset="-{{ $offset }}"
                                    stroke-linecap="round" stroke-width="7"></circle>
                        @endforeach
                    </svg>
                    <div class="absolute text-center">
                        <span class="block text-2xl font-bold leading-tight text-slate-900 tabular-nums">{{ $overallParticipation === null ? '—' : $overallParticipation.'%' }}</span>
                        <span class="text-[11px] font-medium uppercase tracking-wider text-slate-500">Rata-rata</span>
                    </div>
                </div>
                <div class="space-y-2 border-t border-slate-100 pt-3 text-xs">
                    @foreach ($categories as $category)
                        <div class="flex items-center justify-between">
                            <span class="flex items-center gap-2 font-medium text-slate-600">
                                <span class="h-2 w-2 rounded-full" style="background-color: {{ $category['color'] }}"></span>
                                {{ $category['name'] }}
                            </span>
                            <span class="font-bold tabular-nums text-slate-800">{{ $category['percentage'] === null ? '—' : $category['percentage'].'%' }}</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    <section aria-label="Daftar survei" class="dash-rise mt-6 rounded-2xl border border-slate-200/80 bg-white shadow-sm" style="animation-delay: .35s">
        <div class="flex items-center justify-between px-6 pt-5">
            <div>
                <h2 class="text-base font-bold text-slate-900">Survei Terkini</h2>
                <p class="mt-0.5 text-xs text-slate-500">Daftar instrumen dan status partisipasinya</p>
            </div>
            <a href="{{ route('surveys.index') }}" class="inline-flex items-center gap-1 text-xs font-semibold text-blue-600 transition-colors hover:text-blue-700">
                Kelola Semua
                <span class="material-symbols-outlined text-base">arrow_forward</span>
            </a>
        </div>
        <div class="overflow-x-auto px-6 pb-4 pt-4">
            <table class="w-full min-w-[720px] border-collapse text-left">
                <thead>
                    <tr class="border-b border-slate-200/80 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                        <th class="pb-3 pl-1 pr-4 font-bold">Nama Survei</th>
                        <th class="pb-3 pr-4 font-bold">Status</th>
                        <th class="pb-3 pr-4 font-bold">Respons</th>
                        <th class="pb-3 pr-4 font-bold">Periode</th>
                        <th class="pb-3 pr-1 text-right font-bold">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-sm">
                    @forelse ($recentSurveys as $row)
                        @php
                            [$badgeBg, $badgeText] = match ($row['tone']) {
                                'active' => ['bg-blue-50', 'text-blue-700'],
                                'scheduled' => ['bg-sky-50', 'text-sky-700'],
                                'closed' => ['bg-emerald-50', 'text-emerald-700'],
                                default => ['bg-slate-100', 'text-slate-600'],
                            };

                            $actionIcon = $row['action']['icon'] === 'chart' ? 'insights' : 'edit';
                        @endphp
                        <tr class="transition-colors hover:bg-slate-50/70">
                            <td class="py-3.5 pl-1 pr-4">
                                <p class="font-semibold text-slate-900">{{ $row['title'] }}</p>
                                <p class="mt-0.5 text-xs text-slate-500">{{ $row['meta'] }}</p>
                            </td>
                            <td class="py-3.5 pr-4">
                                <span class="inline-flex rounded-full {{ $badgeBg }} px-2.5 py-1 text-[11px] font-bold {{ $badgeText }}">{{ $row['status'] }}</span>
                            </td>
                            <td class="py-3.5 pr-4 text-slate-700">
                                @if ($row['target'] === null)
                                    <span class="text-slate-500">Belum dibuka</span>
                                @elseif ($row['percentage'] === null)
                                    <span class="font-semibold tabular-nums">{{ number_format($row['responses'], 0, ',', '.') }}</span>
                                @else
                                    <div class="flex items-center gap-2.5">
                                        <div class="h-1.5 w-24 overflow-hidden rounded-full bg-slate-100">
                                            <div class="h-full rounded-full {{ $row['tone'] === 'closed' ? 'bg-emerald-600' : 'bg-blue-600' }}" style="width: {{ min(100, $row['percentage']) }}%"></div>
                                        </div>
                                        <span class="font-semibold tabular-nums">{{ number_format($row['responses'], 0, ',', '.') }}/{{ number_format($row['target'], 0, ',', '.') }}</span>
                                    </div>
                                @endif
                            </td>
                            <td class="py-3.5 pr-4 text-xs text-slate-600">{{ $row['periode'] }}</td>
                            <td class="py-3.5 pr-1 text-right">
                                <a href="{{ $row['action']['url'] }}"
                                   class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-semibold transition-colors {{ $row['action']['style'] === 'primary'
                                       ? 'border border-surface-container bg-surface-container-low text-primary hover:bg-surface-container'
                                       : 'border border-slate-200 bg-slate-50 text-slate-700 hover:bg-slate-100' }}">
                                    <span class="material-symbols-outlined text-sm {{ $row['action']['style'] === 'primary' ? '' : 'text-slate-500' }}">{{ $actionIcon }}</span>
                                    {{ $row['action']['label'] }}
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-8 text-center text-sm text-slate-500">
                                Belum ada survei. Buat survei pertama lewat tombol "Survei Baru".
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
