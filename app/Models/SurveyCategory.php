<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'description'])]
class SurveyCategory extends Model
{
    public function surveys(): HasMany
    {
        return $this->hasMany(Survey::class, 'category_id');
    }
}
