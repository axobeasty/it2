<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TestQuestion extends Model
{
    protected $table = 'test_questions';

    protected $fillable = [
        'test_id',
        'type',
        'question_text',
        'options_json',
        'correct_answer_json',
        'points',
        'sort_order',
    ];

    public function test()
    {
        return $this->belongsTo(Test::class, 'test_id');
    }
}
