<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Option extends Model
{
    protected $fillable = [
        'question_id',
        'label',
        'nilai',
    ];

    public function question()
    {
        return $this->belongsTo(Question::class, 'question_id');
    }

    public function answers()
    {
        return $this->hasMany(Answer::class, 'option_id');
    }
}
