<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'creator_id',
    'category_id',
    'title',
    'description',
    'student_target_mode',
    'teacher_target_mode',
    'status',
    'starts_at',
    'ends_at',
    'is_result_published',
])]
class Survey extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_result_published' => 'boolean',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    /**
     * Status tampilan (bukan status database) untuk survei published.
     */
    protected function displayStatus(): Attribute
    {
        return Attribute::get(function (): string {
            if ($this->status === 'closed') {
                return 'Ditutup';
            }

            if ($this->status === 'draft') {
                return 'Draft';
            }

            if ($this->starts_at !== null && now()->lt($this->starts_at)) {
                return 'Dijadwalkan';
            }

            if ($this->ends_at !== null && now()->gt($this->ends_at)) {
                return 'Berakhir';
            }

            return 'Berlangsung';
        });
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(SurveyCategory::class, 'category_id');
    }

    public function targetClasses(): BelongsToMany
    {
        return $this->belongsToMany(SchoolClass::class, 'survey_target_classes', 'survey_id', 'class_id')
            ->withTimestamps();
    }

    public function targetTeachers(): BelongsToMany
    {
        return $this->belongsToMany(Teacher::class, 'survey_target_teachers', 'survey_id', 'teacher_id')
            ->withTimestamps();
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class)->orderBy('position');
    }

    public function responses(): HasMany
    {
        return $this->hasMany(Response::class);
    }
}
