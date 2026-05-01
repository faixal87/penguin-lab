<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Scenario extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'set_no',
        'question_type',
        'title',
        'description',
        'expected_command',
        'hint',
        'hint_2',
        'difficulty',
        'score',
    ];

    protected $casts = [
        'set_no' => 'integer',
        'score' => 'integer',
    ];
}
