<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoginLog extends Model
{
    public const CREATED_AT = 'logged_in_at';
    public const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'activity',
        'ip_address',
        'user_agent',
        'browser',
        'platform',
    ];

    protected $casts = [
        'logged_in_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
