<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'major', 'academic_year', 'is_active'])]
class SchoolClass extends Model
{
    /** @var array<string, string> */
    public const MAJORS = [
        'PPLG' => 'Pengembangan Perangkat Lunak dan Gim',
        'AKL' => 'Akuntansi dan Keuangan Lembaga',
        'TO' => 'Teknik Otomotif',
        'MPLB' => 'Manajemen Perkantoran dan Layanan Bisnis',
        'DKV' => 'Desain Komunikasi Visual',
        'TJKT' => 'Teknik Jaringan Komputer dan Telekomunikasi',
    ];

    protected $table = 'classes';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class, 'class_id');
    }

    public function teachingAssignments(): HasMany
    {
        return $this->hasMany(TeachingAssignment::class, 'class_id');
    }

    public function surveyTargets(): HasMany
    {
        return $this->hasMany(SurveyTargetClass::class, 'class_id');
    }
}
