<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class SchoolClass extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'classes';

    protected $fillable = [
        'lecturer_id',
        'class_name',
        'course_code',
        'terminal_enabled',
    ];

    protected $casts = [
        'terminal_enabled' => 'boolean',
    ];

    public function lecturer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'lecturer_id');
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'class_student', 'class_id', 'student_id')
            ->withPivot('id');
    }
}
