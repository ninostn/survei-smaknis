<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Answer extends Model
{
    protected $fillable = [
        'response_id',
        'question_id',
        'option_id',
        'teks',
    ];

    public function response()
    {
        return $this->belongsTo(Response::class, 'response_id');
    }

    public function question()
    {
        return $this->belongsTo(Question::class, 'question_id');
    }

    public function option()
    {
        return $this->belongsTo(Option::class, 'option_id');
    }
}
