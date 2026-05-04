<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuestionSet extends Model
{
    protected $fillable = ['created_by', 'visibility', 'name', 'description', 'total_marks', 'is_active'];

    protected $casts = [
        'total_marks' => 'integer',
        'is_active' => 'boolean',
    ];

    public function setQuestions(): HasMany
    {
        return $this->hasMany(QuestionSetQuestion::class)->orderBy('sort_order')->orderBy('id');
    }

    public function classes(): BelongsToMany
    {
        return $this->belongsToMany(SchoolClass::class, 'class_question_set', 'question_set_id', 'class_id')
            ->withTimestamps();
    }

    public function assignedStudents(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'question_set_user', 'question_set_id', 'user_id')
            ->withTimestamps();
    }

    public function currentTotal(): int
    {
        return (int) $this->setQuestions->sum(fn (QuestionSetQuestion $item) => $item->mark ?? $item->question->score);
    }

    public function isAssignable(): bool
    {
        return $this->is_active && $this->currentTotal() === 100;
    }
}
