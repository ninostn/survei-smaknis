<?php

namespace App\Support;

use App\Models\Answer;
use App\Models\Response;
use App\Models\Student;
use App\Models\Survey;
use App\Models\Teacher;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Agregasi statistik untuk dashboard admin.
 *
 * Semua angka dihitung dari data nyata (survei, respons, target). Target
 * partisipasi dihitung dengan aturan yang sama dengan SurveyEligibility
 * (akun aktif, profil ada, kelas aktif) agar persentase tidak meleset.
 */
class AdminDashboardStats
{
    private const MONTH_ABBREVIATIONS = [
        1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun',
        'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des',
    ];

    private const DONUT_COLORS = ['#2563eb', '#38bdf8', '#93c5fd', '#94a3b8', '#6ee7b7'];

    /**
     * Susun seluruh data untuk view dashboard admin.
     */
    public static function build(): array
    {
        $now = now();

        $surveys = Survey::query()
            ->with(['category', 'targetClasses', 'targetTeachers.user'])
            ->withCount('responses')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();

        $targets = $surveys->mapWithKeys(
            fn (Survey $survey): array => [
                $survey->id => $survey->status === 'draft' ? 0 : self::targetFor($survey),
            ],
        );

        $activeSurveys = $surveys->filter(
            fn (Survey $survey): bool => $survey->status === 'published'
                && $survey->starts_at !== null
                && $survey->ends_at !== null
                && $now->between($survey->starts_at, $survey->ends_at),
        );

        $activeRatios = self::participationRatios($surveys, $targets, $activeSurveys);
        $overallRatios = self::participationRatios($surveys, $targets, $surveys->reject(
            fn (Survey $survey): bool => $survey->status === 'draft',
        ));

        return [
            'activeCount' => $activeSurveys->count(),
            'endingThisWeek' => $activeSurveys
                ->where('ends_at', '<=', $now->copy()->addDays(7))
                ->count(),
            'totalResponses' => Response::query()->count(),
            'participation' => $activeRatios->isEmpty()
                ? null
                : (int) round($activeRatios->avg() * 100),
            'overallParticipation' => $overallRatios->isEmpty()
                ? null
                : (int) round($overallRatios->avg() * 100),
            'avgRating' => self::avgRating(),
            'trend' => self::trend(),
            'categories' => self::categoryParticipation($surveys, $targets),
            'recentSurveys' => self::recentSurveys($surveys->take(5), $targets),
        ];
    }

    /**
     * Label periode aktif untuk topbar (semester berdasarkan bulan berjalan).
     */
    public static function periodLabel(?Carbon $now = null): string
    {
        $now ??= now();
        $year = (int) $now->format('Y');

        return ((int) $now->format('n')) >= 8
            ? "Semester Ganjil {$year}/".($year + 1)
            : 'Semester Genap '.($year - 1)."/{$year}";
    }

    /**
     * Kumpulkan rasio respons/target dari subset survei (abaikan target 0).
     */
    private static function participationRatios(
        Collection $surveys,
        Collection $targets,
        Collection $subset,
    ): Collection {
        $ids = $subset->pluck('id');

        return $surveys
            ->whereIn('id', $ids)
            ->map(fn (Survey $survey): ?float => $targets[$survey->id] > 0
                ? $survey->responses_count / $targets[$survey->id]
                : null)
            ->filter(fn (?float $ratio): bool => $ratio !== null)
            ->values();
    }

    /**
     * Jumlah responden sasaran satu survei (aturan sama dengan dashboard).
     */
    public static function surveyTarget(Survey $survey): int
    {
        return self::targetFor($survey);
    }

    /**
     * Jumlah responden sasaran satu survei menurut mode targetnya.
     */
    private static function targetFor(Survey $survey): int
    {
        $studentTarget = match ($survey->student_target_mode) {
            'all' => self::eligibleStudentQuery()->count(),
            'classes' => self::eligibleStudentQuery()
                ->whereIn('class_id', $survey->targetClasses->pluck('id'))
                ->count(),
            default => 0,
        };

        $teacherTarget = match ($survey->teacher_target_mode) {
            'all' => Teacher::query()
                ->whereHas('user', fn ($query) => $query->where('is_active', true))
                ->count(),
            'selected' => $survey->targetTeachers
                ->filter(fn (Teacher $teacher): bool => (bool) $teacher->user?->is_active)
                ->count(),
            default => 0,
        };

        return $studentTarget + $teacherTarget;
    }

    private static function eligibleStudentQuery(): Builder
    {
        return Student::query()
            ->whereNotNull('class_id')
            ->whereHas('user', fn ($query) => $query->where('is_active', true))
            ->whereHas('schoolClass', fn ($query) => $query->where('is_active', true));
    }

    /**
     * Rata-rata rating dari jawaban pertanyaan bertipe rating.
     */
    private static function avgRating(): ?float
    {
        $avg = Answer::query()
            ->whereHas('question', fn ($query) => $query->where('type', 'rating'))
            ->whereNotNull('rating_value')
            ->avg('rating_value');

        return $avg === null ? null : round((float) $avg, 1);
    }

    /**
     * Jumlah respons per bulan (6 bulan terakhir) dipisah role siswa/guru.
     */
    private static function trend(): array
    {
        $start = now()->startOfMonth()->subMonths(5);
        $end = now()->endOfMonth();

        $rows = Response::query()
            ->join('users', 'users.id', '=', 'responses.user_id')
            ->whereBetween('responses.submitted_at', [$start, $end])
            ->selectRaw("DATE_FORMAT(responses.submitted_at, '%Y-%m') AS month, users.role AS role, COUNT(*) AS total")
            ->groupBy('month', 'role')
            ->get();

        $months = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i)->startOfMonth();
            $months[$date->format('Y-m')] = [
                'label' => self::MONTH_ABBREVIATIONS[(int) $date->format('n')],
                'student' => 0,
                'teacher' => 0,
            ];
        }

        foreach ($rows as $row) {
            $bucket = $row->role === 'teacher' ? 'teacher' : 'student';

            if (isset($months[$row->month])) {
                $months[$row->month][$bucket] += (int) $row->total;
            }
        }

        return array_values($months);
    }

    /**
     * Partisipasi per kategori survei (survei non-draft), maksimal 5 kategori.
     */
    private static function categoryParticipation(Collection $surveys, Collection $targets): array
    {
        return $surveys
            ->reject(fn (Survey $survey): bool => $survey->status === 'draft')
            ->groupBy(fn (Survey $survey): string => $survey->category?->name ?? 'Tanpa Kategori')
            ->map(fn (Collection $group, string $name): array => [
                'name' => $name,
                'responses' => $group->sum('responses_count'),
                'target' => (int) $group->sum(
                    fn (Survey $survey): int => $targets[$survey->id],
                ),
            ])
            ->map(fn (array $category): array => $category + [
                'percentage' => $category['target'] > 0
                    ? (int) round($category['responses'] / $category['target'] * 100)
                    : null,
            ])
            ->sortByDesc('percentage')
            ->take(5)
            ->values()
            ->map(fn (array $category, int $index): array => $category + [
                'color' => self::DONUT_COLORS[$index % count(self::DONUT_COLORS)],
            ])
            ->all();
    }

    /**
     * Lima survei terbaru dalam bentuk siap-tampil (label, badge, aksi).
     */
    private static function recentSurveys(Collection $recent, Collection $targets): array
    {
        return $recent
            ->map(function (Survey $survey) use ($targets): array {
                $isDraft = $survey->status === 'draft';
                $target = $isDraft ? null : $targets[$survey->id];

                return [
                    'title' => $survey->title,
                    'meta' => implode(' · ', array_filter([
                        $survey->category?->name ?? 'Tanpa Kategori',
                        self::audienceLabel($survey),
                    ])),
                    'status' => $survey->displayStatus,
                    'tone' => self::statusTone($survey),
                    'responses' => $survey->responses_count,
                    'target' => $target,
                    'percentage' => (! $isDraft && $target > 0)
                        ? (int) round($survey->responses_count / $target * 100)
                        : null,
                    'periode' => self::periodeLabel($survey),
                    'action' => $isDraft
                        ? [
                            'label' => 'Kelola',
                            'url' => route('surveys.edit', $survey),
                            'style' => 'neutral',
                            'icon' => 'edit',
                        ]
                        : [
                            'label' => 'Lihat Hasil',
                            'url' => route('surveys.results.show', $survey),
                            'style' => 'primary',
                            'icon' => 'chart',
                        ],
                ];
            })
            ->all();
    }

    private static function audienceLabel(Survey $survey): string
    {
        $audience = [];

        if ($survey->student_target_mode !== 'none') {
            $audience[] = 'Siswa';
        }

        if ($survey->teacher_target_mode !== 'none') {
            $audience[] = 'Guru';
        }

        return implode(' & ', $audience);
    }

    private static function statusTone(Survey $survey): string
    {
        return match ($survey->displayStatus) {
            'Berlangsung' => 'active',
            'Dijadwalkan' => 'scheduled',
            'Draft' => 'draft',
            default => 'closed', // Ditutup, Berakhir
        };
    }

    /**
     * Label periode: tanggal + bulan saja, rentang untuk survei selesai.
     */
    private static function periodeLabel(Survey $survey): string
    {
        if ($survey->starts_at === null || $survey->ends_at === null || $survey->status === 'draft') {
            return '—';
        }

        if ($survey->status === 'published' && now()->between($survey->starts_at, $survey->ends_at)) {
            return self::formatDate($survey->ends_at);
        }

        return self::formatDate($survey->starts_at).' – '.self::formatDate($survey->ends_at);
    }

    private static function formatDate(Carbon $date): string
    {
        return $date->day.' '.self::MONTH_ABBREVIATIONS[$date->month];
    }
}
