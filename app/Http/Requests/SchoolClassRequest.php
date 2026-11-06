<?php

namespace App\Http\Requests;

use App\Models\SchoolClass;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class SchoolClassRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'academic_year' => ['required', 'string', 'max:50'],
        ];
    }

    /**
     * Unik gabungan name + academic_year, mengabaikan kelas yang diedit.
     * Tahun akademik terkunci bila kelas sudah punya siswa atau penugasan.
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $class = $this->route('class');
                $class = $class instanceof SchoolClass ? $class : null;

                $duplicate = SchoolClass::query()
                    ->where('name', $this->input('name'))
                    ->where('academic_year', $this->input('academic_year'))
                    ->when($class !== null, fn ($q) => $q->whereKeyNot($class->getKey()))
                    ->exists();

                if ($duplicate) {
                    $validator->errors()->add(
                        'name',
                        'Kombinasi nama kelas dan tahun akademik sudah ada.',
                    );
                }

                if ($class === null || (string) $this->input('academic_year') === $class->academic_year) {
                    return;
                }

                if ($class->students()->exists() || $class->teachingAssignments()->exists()) {
                    $validator->errors()->add(
                        'academic_year',
                        'Tahun akademik tidak dapat diubah karena kelas sudah memiliki siswa atau penugasan mengajar. Buat kelas baru untuk tahun akademik berikutnya.',
                    );
                }
            },
        ];
    }
}
