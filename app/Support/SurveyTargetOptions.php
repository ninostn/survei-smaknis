<?php

namespace App\Support;

use App\Models\SchoolClass;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Support\Collection;

class SurveyTargetOptions
{
    public static function canTargetAllStudents(User $user): bool
    {
        return $user->role === 'admin';
    }

    /**
     * Kelas aktif yang boleh dijadikan target siswa. Guru dibatasi pada
     * kelas yang tercantum dalam teaching_assignments miliknya.
     *
     * @return Collection<int, SchoolClass>
     */
    public static function studentClasses(User $user): Collection
    {
        if ($user->role === 'teacher' && $user->teacher === null) {
            return new Collection;
        }

        return SchoolClass::query()
            ->where('is_active', true)
            ->when($user->role === 'teacher', fn ($query) => $query->whereHas(
                'teachingAssignments',
                fn ($assignment) => $assignment->where('teacher_id', $user->teacher->id),
            ))
            ->orderBy('name')
            ->get();
    }

    /**
     * Guru yang boleh dijadikan target: profil guru dengan akun aktif.
     *
     * @return Collection<int, Teacher>
     */
    public static function selectableTeachers(): Collection
    {
        return Teacher::query()
            ->with('user')
            ->whereHas('user', fn ($query) => $query->where('role', 'teacher')->where('is_active', true))
            ->orderBy('name')
            ->get();
    }
}
