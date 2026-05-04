<?php

namespace App\Services;

use App\Models\CourseFeedbackControl;
use App\Models\FeedbackAnswer;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class CourseFeedbackService
{
    public function isEnabledFor(User $user): bool
    {
        if (! $user->isStudent()) {
            return false;
        }

        return CourseFeedbackControl::query()
            ->where('is_enabled', true)
            ->where(function (Builder $query) use ($user) {
                $query->where('target_type', 'all')
                    ->orWhere(fn (Builder $roleQuery) => $roleQuery
                        ->where('target_type', 'role')
                        ->where('target_role', 'student'))
                    ->orWhere(fn (Builder $userQuery) => $userQuery
                        ->where('target_type', 'user')
                        ->where('target_user_id', $user->id));

                $classIds = $user->enrolledClasses()->pluck('classes.id');

                $query->orWhere(fn (Builder $classQuery) => $classQuery
                    ->where('target_type', 'class')
                    ->whereIn('target_class_id', $classIds));
            })
            ->exists();
    }

    public function hasSubmitted(User $user): bool
    {
        return FeedbackAnswer::where('user_id', $user->id)->exists();
    }

    public function shouldShowMenu(User $user): bool
    {
        return $user->isStudent() && $this->isEnabledFor($user);
    }

    public function manageableControls(User $user): Collection
    {
        return CourseFeedbackControl::query()
            ->with(['creator', 'targetClass', 'targetUser'])
            ->when($user->isLecturer(), function (Builder $query) use ($user) {
                $classIds = $user->teachingClasses()->pluck('id');
                $studentIds = User::where('role', 'student')
                    ->whereHas('enrolledClasses', fn (Builder $classQuery) => $classQuery->whereIn('classes.id', $classIds))
                    ->pluck('id');

                $query->where('created_by', $user->id)
                    ->where(function (Builder $inner) use ($classIds, $studentIds) {
                        $inner->where(fn (Builder $classQuery) => $classQuery
                            ->where('target_type', 'class')
                            ->whereIn('target_class_id', $classIds))
                            ->orWhere(fn (Builder $studentQuery) => $studentQuery
                                ->where('target_type', 'user')
                                ->whereIn('target_user_id', $studentIds));
                    });
            })
            ->latest()
            ->get();
    }

    public function affectedStudents(array $data, User $actor): Collection
    {
        $query = User::query()->where('role', 'student');

        if ($data['target_type'] === 'role') {
            $query->where('role', $data['target_role']);
        }

        if ($data['target_type'] === 'class') {
            $query->whereHas('enrolledClasses', fn (Builder $classQuery) => $classQuery->where('classes.id', $data['target_class_id']));
        }

        if ($data['target_type'] === 'user') {
            $query->whereKey($data['target_user_id']);
        }

        if ($actor->isLecturer()) {
            $query->whereHas('enrolledClasses', fn (Builder $classQuery) => $classQuery->where('lecturer_id', $actor->id));
        }

        return $query->get();
    }

    public function lecturerStudents(User $lecturer): Collection
    {
        return User::where('role', 'student')
            ->whereHas('enrolledClasses', fn (Builder $query) => $query->where('lecturer_id', $lecturer->id))
            ->orderBy('name')
            ->get();
    }

    public function classesFor(User $user): Collection
    {
        return SchoolClass::query()
            ->when($user->isLecturer(), fn (Builder $query) => $query->where('lecturer_id', $user->id))
            ->orderBy('class_name')
            ->get();
    }
}
