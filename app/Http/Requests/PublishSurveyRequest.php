<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PublishSurveyRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        if ($user === null || ! in_array($user->role, ['admin', 'teacher'], true)) {
            return false;
        }

        return $user->role !== 'teacher' || $user->teacher !== null;
    }

    /**
     * @return array<string, array<int, mixed>|string>
     */
    public function rules(): array
    {
        return [
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'starts_at.required' => 'Waktu mulai wajib diisi.',
            'starts_at.date' => 'Waktu mulai tidak valid.',
            'ends_at.required' => 'Waktu berakhir wajib diisi.',
            'ends_at.date' => 'Waktu berakhir tidak valid.',
            'ends_at.after' => 'Waktu berakhir harus lebih besar daripada waktu mulai.',
        ];
    }
}
