<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuestionSetQuestion extends Model
{
    protected $fillable = ['question_set_id', 'question_bank_id', 'mark', 'sort_order'];

    protected $casts = [
        'mark' => 'integer',
        'sort_order' => 'integer',
    ];

    public function questionSet(): BelongsTo
    {
        return $this->belongsTo(QuestionSet::class);
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(QuestionBank::class, 'question_bank_id');
    }

    public function effectiveMark(): int
    {
        return (int) ($this->mark ?? $this->question->score);
    }
}
