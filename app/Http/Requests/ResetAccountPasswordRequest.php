<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class ResetAccountPasswordRequest extends FormRequest
{
    /**
     * Password admin tidak boleh di-reset lewat modul ini.
     */
    public function authorize(): bool
    {
        $account = $this->route('user');

        return $account instanceof User && $account->role !== 'admin';
    }

    /**
     * @return array<string, array<int, string>|string>
     */
    public function rules(): array
    {
        return [
            'password' => ['required', 'string', 'min:12', 'confirmed'],
        ];
    }
}
