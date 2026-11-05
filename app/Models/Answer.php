<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['response_id', 'question_id', 'answer_text', 'rating_value'])]
class Answer extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'rating_value' => 'integer',
        ];
    }

    public function response(): BelongsTo
    {
        return $this->belongsTo(Response::class);
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    /**
     * Opsi yang dipilih untuk jawaban ini.
     *
     * Kolom `answer_options.question_id` bersifat wajib, jadi saat menulis pivot
     * sertakan nilainya: attach($optionId, ['question_id' => $questionId]).
     */
    public function selectedOptions(): BelongsToMany
    {
        return $this->belongsToMany(QuestionOption::class, 'answer_options', 'answer_id', 'option_id')
            ->withPivot('question_id')
            ->withTimestamps();
    }

    public function answerOptions(): HasMany
    {
        return $this->hasMany(AnswerOption::class);
    }
}
