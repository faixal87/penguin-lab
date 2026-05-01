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
        'answer',
        'is_correct',
        'score_awarded',
        'hint_used',
    ];

    protected $casts = [
        'set_no' => 'integer',
        'is_correct' => 'boolean',
        'score_awarded' => 'decimal:2',
        'hint_used' => 'boolean',
    ];

    public function scenario(): BelongsTo
    {
        return $this->belongsTo(Scenario::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
