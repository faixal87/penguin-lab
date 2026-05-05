<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiFeedbackSummary extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'generated_by',
        'scope_type',
        'class_id',
        'prompt_data',
        'summary',
    ];

    protected $casts = [
        'prompt_data' => 'array',
        'created_at' => 'datetime',
    ];

    public function generator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }
}
