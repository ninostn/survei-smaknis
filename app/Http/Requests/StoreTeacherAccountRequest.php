<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTeacherAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Middleware auth + role:admin sudah menjaga route ini.
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
            // NIP opsional, tapi wajib unik bila diisi. Tidak semua guru punya NIP.
            'nip' => ['nullable', 'string', 'max:255', 'unique:teachers,nip'],
            'password' => ['required', 'string', 'min:12', 'confirmed'],
        ];
    }
}
