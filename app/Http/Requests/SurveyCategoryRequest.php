<?php

namespace App\Http\Requests;

use App\Models\SurveyCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SurveyCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Rapikan spasi agar nama yang hanya berbeda spasi dianggap sama.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => $this->normalizeText($this->input('name')),
            'description' => $this->normalizeText($this->input('description')),
        ]);
    }

    /**
     * @return array<string, array<int, mixed>|string>
     */
    public function rules(): array
    {
        $category = $this->route('category');
        $category = $category instanceof SurveyCategory ? $category : null;

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('survey_categories', 'name')->ignore($category?->id),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Nama kategori survei wajib diisi.',
            'name.unique' => 'Nama kategori survei sudah ada.',
            'name.max' => 'Nama kategori survei maksimal 255 karakter.',
            'description.max' => 'Deskripsi kategori survei maksimal 1000 karakter.',
        ];
    }

    private function normalizeText(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        $value = preg_replace('/\s+/u', ' ', trim($value));

        return $value === '' ? null : $value;
    }
}
