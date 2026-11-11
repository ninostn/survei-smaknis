<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class QuestionRequest extends FormRequest
{
    public const TYPES = [
        'single_choice',
        'multiple_choice',
        'rating',
        'short_text',
        'long_text',
    ];

    private const OPTION_TYPES = [
        'single_choice',
        'multiple_choice',
    ];

    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null
            && in_array($user->role, ['admin', 'teacher'], true)
            && ($user->role !== 'teacher' || $user->teacher !== null);
    }

    protected function prepareForValidation(): void
    {
        $type = $this->input('type');
        $options = $this->input('option_texts');

        if (is_array($options)) {
            $options = array_map(
                fn ($option) => is_string($option) ? trim($option) : $option,
                $options,
            );
        }

        $this->merge([
            'question_text' => is_string($this->input('question_text'))
                ? trim($this->input('question_text'))
                : $this->input('question_text'),
            'is_required' => $this->boolean('is_required'),
            'option_texts' => $this->typeNeedsOptions($type) && is_array($options) ? $options : [],
        ]);
    }

    /**
     * @return array<string, array<int, mixed>|string>
     */
    public function rules(): array
    {
        return [
            'question_text' => ['required', 'string', 'max:2000'],
            'type' => ['required', 'string', Rule::in(self::TYPES)],
            'is_required' => ['boolean'],
            'option_texts' => ['nullable', 'array', 'max:20'],
            'option_texts.*' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if (! $this->typeNeedsOptions($this->input('type'))) {
                    return;
                }

                $options = $this->input('option_texts', []);

                if (! is_array($options) || count($options) < 2) {
                    $validator->errors()->add(
                        'option_texts',
                        'Pertanyaan pilihan wajib memiliki minimal dua opsi jawaban.',
                    );

                    return;
                }

                foreach ($options as $index => $option) {
                    if (! is_string($option) || trim($option) === '') {
                        $validator->errors()->add(
                            'option_texts.'.$index,
                            'Teks opsi jawaban tidak boleh kosong.',
                        );
                    }
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
            'question_text.required' => 'Teks pertanyaan wajib diisi.',
            'question_text.max' => 'Teks pertanyaan maksimal 2000 karakter.',
            'type.required' => 'Tipe pertanyaan wajib dipilih.',
            'type.in' => 'Tipe pertanyaan tidak valid.',
            'option_texts.array' => 'Opsi jawaban tidak valid.',
            'option_texts.max' => 'Opsi jawaban maksimal 20 baris.',
            'option_texts.*.string' => 'Teks opsi jawaban tidak valid.',
            'option_texts.*.max' => 'Teks opsi jawaban maksimal 255 karakter.',
        ];
    }

    private function typeNeedsOptions(mixed $type): bool
    {
        return is_string($type) && in_array($type, self::OPTION_TYPES, true);
    }
}
