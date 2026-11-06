<?php

namespace App\Http\Requests;

use App\Models\SchoolClass;
use App\Models\Teacher;
use App\Models\TeachingAssignment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class TeachingAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Tahun akademik tidak divalidasi di sini: nilainya selalu diturunkan
     * dari kelas terpilih di controller, bukan dari browser.
     *
     * @return array<string, array<int, mixed>|string>
     */
    public function rules(): array
    {
        $assignment = $this->route('assignment');
        $assignment = $assignment instanceof TeachingAssignment ? $assignment : null;

        $unique = Rule::unique('teaching_assignments')
            ->where('class_id', $this->input('class_id'))
            ->where('subject_id', $this->input('subject_id'))
            ->where('academic_year', $this->classAcademicYear())
            ->ignore($assignment?->id);

        return [
            'teacher_id' => ['required', 'integer', 'exists:teachers,id', $unique],
            'class_id' => ['required', 'integer', 'exists:classes,id'],
            'subject_id' => ['required', 'integer', 'exists:subjects,id'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $this->validateTeacher($validator);
                $this->validateClass($validator);
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'teacher_id.unique' => 'Kombinasi guru, kelas, mata pelajaran, dan tahun akademik sudah ada.',
            'class_id.exists' => 'Kelas yang dipilih tidak ditemukan.',
            'subject_id.exists' => 'Mata pelajaran yang dipilih tidak ditemukan.',
        ];
    }

    /**
     * Foreign key saja tidak cukup: user di balik profil teacher harus
     * ber-role teacher dan akunnya aktif. Dropdown bukan mekanisme keamanan.
     */
    private function validateTeacher(Validator $validator): void
    {
        $teacher = Teacher::with('user')->find($this->input('teacher_id'));

        if ($teacher === null || $teacher->user === null || $teacher->user->role !== 'teacher') {
            $validator->errors()->add('teacher_id', 'Guru yang dipilih tidak valid.');

            return;
        }

        if (! $teacher->user->is_active) {
            $validator->errors()->add('teacher_id', 'Guru yang dipilih tidak aktif.');
        }
    }

    /**
     * Penugasan baru wajib kelas aktif. Saat edit, kelas lama yang sudah
     * nonaktif boleh dipertahankan; kelas pengganti tetap wajib aktif.
     */
    private function validateClass(Validator $validator): void
    {
        $class = SchoolClass::find($this->input('class_id'));
        $assignment = $this->route('assignment');
        $assignment = $assignment instanceof TeachingAssignment ? $assignment : null;

        $isCurrentClass = $assignment !== null
            && $class !== null
            && (int) $assignment->class_id === (int) $class->id;

        if ($class !== null && ! $class->is_active && ! $isCurrentClass) {
            $validator->errors()->add('class_id', 'Kelas yang dipilih tidak ditemukan atau tidak aktif.');
        }
    }

    /**
     * Tahun akademik kelas terpilih, dipakai untuk cek kombinasi unik agar
     * konsisten dengan nilai yang nanti disimpan.
     */
    private function classAcademicYear(): ?string
    {
        $class = SchoolClass::find($this->input('class_id'));

        return $class?->academic_year;
    }
}
