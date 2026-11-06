<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAccountRequest extends FormRequest
{
    /**
     * Akun bertipe admin (termasuk akun admin sendiri) tidak boleh
     * diubah lewat modul manajemen akun. Role juga tidak pernah diubah.
     */
    public function authorize(): bool
    {
        $account = $this->route('user');

        return $account instanceof User && $account->role !== 'admin';
    }

    /**
     * @return array<string, array<int, mixed>|string>
     */
    public function rules(): array
    {
        /** @var User $account */
        $account = $this->route('user');

        $rules = [
            'username' => ['required', 'string', 'max:255', Rule::unique('users', 'username')->ignore($account->id)],
            'name' => ['required', 'string', 'max:255'],
            'is_active' => ['boolean'],
        ];

        if ($account->role === 'teacher') {
            $rules['nip'] = ['nullable', 'string', 'max:255'];
            if ($account->teacher !== null) {
                $rules['nip'][] = Rule::unique('teachers', 'nip')->ignore($account->teacher->id);
            }
        } else {
            $rules['nis'] = ['required', 'string', 'max:255'];
            $rules['class_id'] = ['required', 'integer', 'exists:classes,id,is_active,1'];
            if ($account->student !== null) {
                $rules['nis'][] = Rule::unique('students', 'nis')->ignore($account->student->id);
            }
        }

        // Tidak ada validasi password: edit profil tidak pernah mengubah password.
        return $rules;
    }
}
