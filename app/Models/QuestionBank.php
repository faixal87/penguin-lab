<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuestionBank extends Model
{
    protected $table = 'question_bank';

    protected $fillable = [
        'created_by', 'visibility', 'title', 'description', 'category', 'difficulty',
        'score', 'expected_answer', 'hint_1', 'hint_2', 'explanation', 'is_active',
        'source_scenario_id',
    ];

    protected $casts = [
        'score' => 'integer',
        'is_active' => 'boolean',
    ];
}
