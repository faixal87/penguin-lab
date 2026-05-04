<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;

#[Fillable([
    'name',
    'email',
    'password',
    'role',
    'matric_no',
    'status',
    'last_login_at',
    'last_login_ip',
    'last_user_agent',
    'profile_photo',
    'default_avatar',
    'phone_no',
    'program',
    'semester',
    'class_name',
    'registration_no',
    'staff_no',
    'department',
    'linux_username',
    'container_name',
    'container_status',
    'guacamole_connection_status',
    'terminal_last_started_at',
    'terminal_last_stopped_at',
    'terminal_enabled',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'terminal_last_started_at' => 'datetime',
            'terminal_last_stopped_at' => 'datetime',
            'terminal_enabled' => 'boolean',
            'password' => 'hashed',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isLecturer(): bool
    {
        return $this->role === 'lecturer';
    }

    public function isStudent(): bool
    {
        return $this->role === 'student';
    }

    public function teachingClasses(): HasMany
    {
        return $this->hasMany(SchoolClass::class, 'lecturer_id');
    }

    public function enrolledClasses(): BelongsToMany
    {
        return $this->belongsToMany(SchoolClass::class, 'class_student', 'student_id', 'class_id')
            ->withPivot('id');
    }

    public function assignedQuestionSets(): BelongsToMany
    {
        return $this->belongsToMany(QuestionSet::class, 'question_set_user', 'user_id', 'question_set_id')
            ->withTimestamps();
    }

    public function loginLogs(): HasMany
    {
        return $this->hasMany(LoginLog::class);
    }

    public function studentAnswers(): HasMany
    {
        return $this->hasMany(StudentAnswer::class);
    }

    public function feedbackAnswers(): HasMany
    {
        return $this->hasMany(FeedbackAnswer::class);
    }

    public function notificationReads(): HasMany
    {
        return $this->hasMany(NotificationRead::class);
    }

    public function profilePhotoUrl(): ?string
    {
        if ($this->profile_photo) {
            return asset('storage/' . $this->profile_photo);
        }

        return asset('assets/avatars/' . ($this->default_avatar ?: 'penguin-1.svg'));
    }

    public static function defaultAvatars(): array
    {
        return [
            'penguin-1.svg' => 'Ubuntu Penguin',
            'penguin-2.svg' => 'Neon Penguin',
            'penguin-3.svg' => 'Terminal Penguin',
            'penguin-4.svg' => 'Cyber Penguin',
            'penguin-5.svg' => 'Lab Penguin',
        ];
    }
}
