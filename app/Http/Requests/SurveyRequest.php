<?php

namespace App\Http\Requests;

use App\Support\SurveyTargetOptions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SurveyRequest extends FormRequest
{
    /**
     * Route middleware membatasi role, tetapi guru tanpa profil tetap
     * ditolak sebelum validasi dijalankan.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        if ($user === null || ! in_array($user->role, ['admin', 'teacher'], true)) {
            return false;
        }

        return $user->role !== 'teacher' || $user->teacher !== null;
    }

    protected function prepareForValidation(): void
    {
        $title = $this->input('title');
        $description = $this->input('description');

        $this->merge([
            'title' => is_string($title) ? trim($title) : $title,
            'description' => is_string($description) && trim($description) === ''
                ? null
                : (is_string($description) ? trim($description) : $description),
        ]);

        // Default none hanya untuk create. Pada update, mode target wajib
        // dikirim eksplisit agar request parsial tidak menghapus pivot.
        if ($this->routeIs('surveys.store')) {
            $this->merge([
                'student_target_mode' => $this->input('student_target_mode', 'none'),
                'teacher_target_mode' => $this->input('teacher_target_mode', 'none'),
            ]);
        }
    }

    /**
     * @return array<string, array<int, mixed>|string>
     */
    public function rules(): array
    {
        $user = $this->user();
        $allowedClassIds = $user === null
            ? []
            : SurveyTargetOptions::studentClasses($user)->modelKeys();

        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'category_id' => ['required', 'integer', 'exists:survey_categories,id'],
            'student_target_mode' => ['required', 'string', Rule::in(['none', 'all', 'classes'])],
            'teacher_target_mode' => ['required', 'string', Rule::in(['none', 'all', 'selected'])],
            'target_class_ids' => ['required_if:student_target_mode,classes', 'array', 'min:1'],
            'target_class_ids.*' => ['integer', 'distinct', Rule::in($allowedClassIds)],
            'target_teacher_ids' => ['required_if:teacher_target_mode,selected', 'array', 'min:1'],
            'target_teacher_ids.*' => [
                'integer',
                'distinct',
                Rule::in(SurveyTargetOptions::selectableTeachers()->modelKeys()),
            ],
        ];
    }

    /**
     * Guru tidak boleh menargetkan seluruh siswa, meskipun opsi tersebut
     * dikirim langsung ke endpoint.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($this->input('student_target_mode') === 'all'
                    && $this->user()?->role === 'teacher') {
                    $validator->errors()->add(
                        'student_target_mode',
                        'Guru tidak dapat menargetkan semua siswa.',
                    );
                }
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Judul survei wajib diisi.',
            'title.max' => 'Judul survei maksimal 255 karakter.',
            'description.max' => 'Deskripsi survei maksimal 5000 karakter.',
            'category_id.required' => 'Kategori survei wajib dipilih.',
            'category_id.exists' => 'Kategori survei yang dipilih tidak ditemukan.',
            'student_target_mode.required' => 'Mode target siswa wajib dipilih.',
            'student_target_mode.in' => 'Mode target siswa tidak valid.',
            'teacher_target_mode.required' => 'Mode target guru wajib dipilih.',
            'teacher_target_mode.in' => 'Mode target guru tidak valid.',
            'target_class_ids.required_if' => 'Pilih minimal satu kelas target.',
            'target_class_ids.min' => 'Pilih minimal satu kelas target.',
            'target_class_ids.*.integer' => 'Kelas target yang dipilih tidak valid.',
            'target_class_ids.*.in' => 'Kelas target tidak valid, tidak aktif, atau bukan kelas yang Anda ajar.',
            'target_teacher_ids.required_if' => 'Pilih minimal satu guru target.',
            'target_teacher_ids.min' => 'Pilih minimal satu guru target.',
            'target_teacher_ids.*.integer' => 'Guru target yang dipilih tidak valid.',
            'target_teacher_ids.*.in' => 'Guru target tidak valid atau akunnya tidak aktif.',
        ];
    }
}
