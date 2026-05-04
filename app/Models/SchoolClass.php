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
        'semester_id',
        'class_name',
        'course_code',
        'terminal_enabled',
        'question_set_id',
    ];

    protected $casts = [
        'terminal_enabled' => 'boolean',
    ];

    public function questionSet(): BelongsTo
    {
        return $this->belongsTo(QuestionSet::class);
    }

    public function questionSets(): BelongsToMany
    {
        return $this->belongsToMany(QuestionSet::class, 'class_question_set', 'class_id', 'question_set_id')
            ->withTimestamps();
    }

    public function lecturer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'lecturer_id');
    }

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'class_student', 'class_id', 'student_id')
            ->withPivot('id');
    }
}
