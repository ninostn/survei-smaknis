<?php

namespace App\Http\Requests;

use App\Support\SurveyTargetOptions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use App\Models\SchoolClass;

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
        $this->merge([
            'student_target_mode' => $this->input(
                'student_target_mode',
                $this->user()?->role === 'admin' ? 'all' : 'classes',
            ),
            'teacher_target_mode' => 'none',
        ]);

        $classIds = collect((array) $this->input('target_class_ids', []));
        $majors = collect((array) $this->input('target_majors', []));
        $years = collect((array) $this->input('target_academic_years', []));
        if ($majors->isNotEmpty() || $years->isNotEmpty()) {
            $classIds = $classIds->merge(SchoolClass::query()
                ->where('is_active', true)
                ->when($majors->isNotEmpty(), fn ($q) => $q->whereIn('major', $majors))
                ->when($years->isNotEmpty(), fn ($q) => $q->whereIn('academic_year', $years))
                ->pluck('id'));
        }
        $this->merge(['target_class_ids' => $classIds->unique()->values()->all()]);
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
            'student_target_mode' => ['required', 'string', Rule::in(['all', 'classes'])],
            'teacher_target_mode' => ['required', 'string', Rule::in(['none'])],
            'target_class_ids' => ['required_if:student_target_mode,classes', 'array', 'min:1'],
            'target_class_ids.*' => ['integer', 'distinct', Rule::in($allowedClassIds)],
            'target_majors' => ['array'],
            // Filter target memakai nilai major yang sudah tersimpan agar data legacy tetap kompatibel.
            'target_majors.*' => ['string', 'distinct'],
            'target_academic_years' => ['array'],
            'target_academic_years.*' => ['string', 'distinct'],
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
            'student_target_mode.in' => 'Pilih target siswa yang valid.',
            'teacher_target_mode.required' => 'Mode target guru wajib dipilih.',
            'teacher_target_mode.in' => 'Target guru tidak digunakan.',
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
