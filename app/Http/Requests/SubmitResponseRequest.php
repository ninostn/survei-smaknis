<?php

namespace App\Http\Requests;

use App\Models\Question;
use App\Models\Survey;
use App\Support\SurveyEligibility;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Validasi payload jawaban survei.
 *
 * Payload hanya berisi jawaban; identitas respons (user, survei, response)
 * selalu diambil dari konteks server, bukan dari input pengguna.
 */
class SubmitResponseRequest extends FormRequest
{
    /**
     * Atribut identitas yang tidak boleh dikirim sebagai payload.
     */
    private const IDENTITY_KEYS = [
        'user_id',
        'survey_id',
        'response_id',
        'question_id',
        'submitted_at',
    ];

    /**
     * Field jawaban yang sah untuk setiap tipe pertanyaan.
     */
    private const ALLOWED_FIELDS = [
        'single_choice' => ['option_id'],
        'multiple_choice' => ['option_ids'],
        'rating' => ['rating'],
        'short_text' => ['text'],
        'long_text' => ['text'],
    ];

    public function authorize(): bool
    {
        $user = $this->user();
        $survey = $this->survey();

        return $user !== null
            && $survey !== null
            && SurveyEligibility::allows($user, $survey);
    }

    protected function prepareForValidation(): void
    {
        $answers = $this->input('answers');

        if (! is_array($answers)) {
            return;
        }

        foreach ($answers as $questionId => $answer) {
            if (! is_array($answer) || ! array_key_exists('text', $answer)) {
                continue;
            }

            if (is_string($answer['text'])) {
                $text = trim($answer['text']);
                $answers[$questionId]['text'] = $text === '' ? null : $text;
            }
        }

        $this->merge(['answers' => $answers]);
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'answers' => ['array'],
            'answers.*' => ['array'],
            'answers.*.option_id' => ['nullable', 'integer'],
            'answers.*.option_ids' => ['nullable', 'array'],
            'answers.*.option_ids.*' => ['integer', 'distinct'],
            'answers.*.rating' => ['nullable', 'integer', 'between:1,5'],
            'answers.*.text' => ['nullable', 'string'],
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                foreach (self::IDENTITY_KEYS as $key) {
                    if ($this->has($key)) {
                        $validator->errors()->add(
                            $key,
                            'Payload tidak boleh memuat data identitas respons.',
                        );
                    }
                }

                $survey = $this->survey();
                $answers = $this->input('answers');

                if ($survey === null || ($answers !== null && ! is_array($answers))) {
                    return;
                }

                $answers ??= [];
                $questions = $survey->questions()->with('options')->get()->keyBy('id');

                foreach (array_keys($answers) as $questionId) {
                    if (! $questions->has((int) $questionId)) {
                        $validator->errors()->add(
                            'answers',
                            'Terdapat pertanyaan yang bukan bagian dari survei ini.',
                        );
                    }
                }

                foreach ($questions as $question) {
                    $answer = $answers[$question->id] ?? [];

                    if (! is_array($answer)) {
                        continue;
                    }

                    $this->validateAnswer($validator, $question, $answer);
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
            'answers.array' => 'Data jawaban tidak valid.',
            'answers.*.array' => 'Data jawaban tidak valid.',
            'answers.*.option_id.integer' => 'Pilihan jawaban tidak valid.',
            'answers.*.option_ids.array' => 'Pilihan jawaban tidak valid.',
            'answers.*.option_ids.*.integer' => 'Pilihan jawaban tidak valid.',
            'answers.*.option_ids.*.distinct' => 'Pilihan jawaban tidak boleh duplikat.',
            'answers.*.rating.integer' => 'Nilai rating harus berupa angka bulat.',
            'answers.*.rating.between' => 'Nilai rating harus antara 1 sampai 5.',
            'answers.*.text.string' => 'Jawaban teks tidak valid.',
        ];
    }

    /**
     * @param  array<int|string, mixed>  $answer
     */
    private function validateAnswer(Validator $validator, Question $question, array $answer): void
    {
        $allowed = self::ALLOWED_FIELDS[$question->type] ?? [];

        if ($allowed === [] || array_diff(array_keys($answer), $allowed) !== []) {
            $validator->errors()->add(
                'answers.'.$question->id,
                'Jenis jawaban tidak sesuai dengan tipe pertanyaan.',
            );

            return;
        }

        match ($question->type) {
            'single_choice' => $this->validateSingleChoice($validator, $question, $answer),
            'multiple_choice' => $this->validateMultipleChoice($validator, $question, $answer),
            'rating' => $this->validateRating($validator, $question, $answer),
            'short_text' => $this->validateText($validator, $question, $answer, 255),
            default => $this->validateText($validator, $question, $answer, 5000),
        };
    }

    /**
     * @param  array<int|string, mixed>  $answer
     */
    private function validateSingleChoice(Validator $validator, Question $question, array $answer): void
    {
        $optionId = $answer['option_id'] ?? null;

        if ($optionId === null || $optionId === '') {
            $this->addRequiredError($validator, $question, 'option_id');

            return;
        }

        if (! is_numeric($optionId)
            || ! $question->options->contains(fn ($option) => $option->id === (int) $optionId)) {
            $validator->errors()->add(
                'answers.'.$question->id.'.option_id',
                'Pilihan jawaban tidak valid untuk pertanyaan ini.',
            );
        }
    }

    /**
     * @param  array<int|string, mixed>  $answer
     */
    private function validateMultipleChoice(Validator $validator, Question $question, array $answer): void
    {
        $optionIds = $answer['option_ids'] ?? null;

        if (! is_array($optionIds) || $optionIds === []) {
            $this->addRequiredError($validator, $question, 'option_ids');

            return;
        }

        $validIds = $question->options->pluck('id')->all();

        foreach ($optionIds as $index => $optionId) {
            if (! is_numeric($optionId) || ! in_array((int) $optionId, $validIds, true)) {
                $validator->errors()->add(
                    'answers.'.$question->id.'.option_ids.'.$index,
                    'Pilihan jawaban tidak valid untuk pertanyaan ini.',
                );
            }
        }
    }

    /**
     * @param  array<int|string, mixed>  $answer
     */
    private function validateRating(Validator $validator, Question $question, array $answer): void
    {
        $rating = $answer['rating'] ?? null;

        if ($rating === null || $rating === '') {
            $this->addRequiredError($validator, $question, 'rating');
        }
    }

    /**
     * @param  array<int|string, mixed>  $answer
     */
    private function validateText(Validator $validator, Question $question, array $answer, int $max): void
    {
        $text = $answer['text'] ?? null;

        if ($text === null || $text === '') {
            $this->addRequiredError($validator, $question, 'text');

            return;
        }

        if (is_string($text) && mb_strlen($text) > $max) {
            $validator->errors()->add(
                'answers.'.$question->id.'.text',
                'Jawaban maksimal '.$max.' karakter.',
            );
        }
    }

    private function addRequiredError(Validator $validator, Question $question, string $field): void
    {
        if (! $question->is_required) {
            return;
        }

        $validator->errors()->add(
            'answers.'.$question->id.'.'.$field,
            'Jawaban untuk pertanyaan ini wajib diisi.',
        );
    }

    private function survey(): ?Survey
    {
        $survey = $this->route('survey');

        return $survey instanceof Survey ? $survey : null;
    }
}
