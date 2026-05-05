<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentAnswer extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'exam_session_id',
        'set_no',
        'scenario_id',
        'question_set_id',
        'question_bank_id',
        'question_set_question_id',
        'answer',
        'is_correct',
        'score_awarded',
        'hint_used',
        'hint_level',
    ];

    protected $casts = [
        'set_no' => 'integer',
        'is_correct' => 'boolean',
        'score_awarded' => 'decimal:2',
        'hint_used' => 'boolean',
        'hint_level' => 'integer',
    ];

    public function scenario(): BelongsTo
    {
        return $this->belongsTo(Scenario::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(QuestionBank::class, 'question_bank_id');
    }

    public function questionSet(): BelongsTo
    {
        return $this->belongsTo(QuestionSet::class, 'question_set_id');
    }

    public function setQuestion(): BelongsTo
    {
        return $this->belongsTo(QuestionSetQuestion::class, 'question_set_question_id');
    }
}
