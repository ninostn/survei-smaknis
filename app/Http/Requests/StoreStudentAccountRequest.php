<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreStudentAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>|string>
     */
    public function rules(): array
    {
        return [
            'username' => ['required', 'string', 'max:255', 'unique:users,username'],
            'name' => ['required', 'string', 'max:255'],
            'nis' => ['required', 'string', 'max:255', 'unique:students,nis'],
            // Kelas wajib dan hanya boleh kelas yang aktif.
            'class_id' => ['required', 'integer', 'exists:classes,id,is_active,1'],
            'password' => ['required', 'string', 'min:12', 'confirmed'],
        ];
    }
}
