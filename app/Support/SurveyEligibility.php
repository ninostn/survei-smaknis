<?php

namespace App\Support;

use App\Models\Survey;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;

/**
 * Satu sumber aturan eligibility pengisian survei.
 *
 * Dipakai untuk dua kebutuhan berbeda:
 * - availableFor(): query daftar survei yang bisa diisi (tanpa query per survei).
 * - allows(): pemeriksaan final untuk satu survei (form dan submit).
 *
 * Fail closed: data profil atau target yang tidak valid menghasilkan penolakan,
 * bukan akses.
 */
class SurveyEligibility
{
    /**
     * Query survei yang dapat diisi user saat ini.
     */
    public static function availableFor(User $user, ?CarbonInterface $now = null): Builder
    {
        $now ??= now();

        $query = Survey::query()
            ->where('status', 'published')
            ->whereNotNull('starts_at')
            ->whereNotNull('ends_at')
            ->where('starts_at', '<=', $now)
            ->where('ends_at', '>=', $now)
            ->whereDoesntHave('responses', fn (Builder $responses) => $responses->where('user_id', $user->id));

        if (! $user->is_active) {
            return $query->whereRaw('1 = 0');
        }

        return match ($user->role) {
            'student' => self::onlyForStudent($query, $user),
            'teacher' => self::onlyForTeacher($query, $user),
            // admin dan role tak dikenal tidak pernah menjadi responden.
            default => $query->whereRaw('1 = 0'),
        };
    }

    /**
     * Pemeriksaan lengkap untuk satu survei.
     */
    public static function allows(User $user, Survey $survey, ?CarbonInterface $now = null): bool
    {
        $now ??= now();

        if (! $user->is_active) {
            return false;
        }

        if ($user->role === 'student') {
            $student = $user->student;

            if ($student === null || $student->class_id === null) {
                return false;
            }

            if (! $student->schoolClass?->is_active) {
                return false;
            }
        } elseif ($user->role === 'teacher') {
            if ($user->teacher === null) {
                return false;
            }
        } else {
            return false;
        }

        if ($survey->status !== 'published') {
            return false;
        }

        if ($survey->starts_at === null || $survey->ends_at === null) {
            return false;
        }

        if ($now->lt($survey->starts_at) || $now->gt($survey->ends_at)) {
            return false;
        }

        if (! self::matchesTarget($user, $survey)) {
            return false;
        }

        return ! $survey->responses()->where('user_id', $user->id)->exists();
    }

    /**
     * Batasi query pada target student yang sesuai.
     */
    private static function onlyForStudent(Builder $query, User $user): Builder
    {
        $student = $user->student;

        if ($student === null || $student->class_id === null) {
            return $query->whereRaw('1 = 0');
        }

        if (! $student->schoolClass?->is_active) {
            return $query->whereRaw('1 = 0');
        }

        $classId = $student->class_id;

        return $query->where(function (Builder $target) use ($classId): void {
            $target->where('student_target_mode', 'all')
                ->orWhere(function (Builder $classes) use ($classId): void {
                    $classes->where('student_target_mode', 'classes')
                        ->whereHas('targetClasses', fn (Builder $pivot) => $pivot->where('classes.id', $classId));
                });
        });
    }

    /**
     * Batasi query pada target teacher yang sesuai.
     */
    private static function onlyForTeacher(Builder $query, User $user): Builder
    {
        $teacher = $user->teacher;

        if ($teacher === null) {
            return $query->whereRaw('1 = 0');
        }

        $teacherId = $teacher->id;

        return $query->where(function (Builder $target) use ($teacherId): void {
            $target->where('teacher_target_mode', 'all')
                ->orWhere(function (Builder $selected) use ($teacherId): void {
                    $selected->where('teacher_target_mode', 'selected')
                        ->whereHas('targetTeachers', fn (Builder $pivot) => $pivot->where('teachers.id', $teacherId));
                });
        });
    }

    /**
     * Cocokkan user dengan konfigurasi target survei.
     */
    private static function matchesTarget(User $user, Survey $survey): bool
    {
        if ($user->role === 'student') {
            $student = $user->student;

            if ($student === null || $student->class_id === null) {
                return false;
            }

            return match ($survey->student_target_mode) {
                'all' => true,
                'classes' => $survey->targetClasses()->where('classes.id', $student->class_id)->exists(),
                default => false,
            };
        }

        $teacher = $user->teacher;

        if ($teacher === null) {
            return false;
        }

        return match ($survey->teacher_target_mode) {
            'all' => true,
            'selected' => $survey->targetTeachers()->where('teachers.id', $teacher->id)->exists(),
            default => false,
        };
    }
}
