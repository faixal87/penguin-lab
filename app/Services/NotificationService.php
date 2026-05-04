<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\NotificationRead;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class NotificationService
{
    public function create(array $data): Notification
    {
        return Notification::create($data);
    }

    public function visibleFor(User $user): Collection
    {
        return $this->visibleQuery($user)
            ->with(['sender', 'targetClass', 'targetUser', 'reads' => fn ($query) => $query->where('user_id', $user->id)])
            ->latest('created_at')
            ->get();
    }

    public function unreadCount(User $user): int
    {
        return $this->visibleQuery($user)
            ->whereDoesntHave('reads', fn (Builder $query) => $query
                ->where('user_id', $user->id)
                ->whereNotNull('read_at'))
            ->count();
    }

    public function popupFor(User $user): ?Notification
    {
        return $this->visibleQuery($user)
            ->whereDoesntHave('reads', fn (Builder $query) => $query
                ->where('user_id', $user->id)
                ->whereNotNull('dismissed_at'))
            ->latest('created_at')
            ->first();
    }

    public function unreadUndismissedFor(User $user): Collection
    {
        return $this->visibleQuery($user)
            ->whereDoesntHave('reads', fn (Builder $query) => $query
                ->where('user_id', $user->id)
                ->where(function (Builder $readQuery) {
                    $readQuery->whereNotNull('read_at')
                        ->orWhereNotNull('dismissed_at');
                }))
            ->latest('created_at')
            ->take(10)
            ->get();
    }

    public function markRead(Notification $notification, User $user): void
    {
        NotificationRead::updateOrCreate(
            ['notification_id' => $notification->id, 'user_id' => $user->id],
            ['read_at' => now()]
        );
    }

    public function dismiss(Notification $notification, User $user): void
    {
        NotificationRead::updateOrCreate(
            ['notification_id' => $notification->id, 'user_id' => $user->id],
            ['read_at' => now(), 'dismissed_at' => now()]
        );
    }

    public function canSee(Notification $notification, User $user): bool
    {
        return $this->visibleQuery($user)->whereKey($notification->id)->exists();
    }

    public function canManage(Notification $notification, User $user): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if (! $user->isLecturer() || $notification->sender_id !== $user->id) {
            return false;
        }

        if ($notification->target_type === 'class') {
            return $user->teachingClasses()->whereKey($notification->target_class_id)->exists();
        }

        if ($notification->target_type === 'user') {
            return $user->teachingClasses()
                ->whereHas('students', fn (Builder $query) => $query->where('users.id', $notification->target_user_id))
                ->exists();
        }

        return false;
    }

    private function visibleQuery(User $user): Builder
    {
        return Notification::query()
            ->where('is_active', true)
            ->where(function (Builder $query) use ($user) {
                $query->where('target_type', 'all')
                    ->orWhere(fn (Builder $roleQuery) => $roleQuery
                        ->where('target_type', 'role')
                        ->where('target_role', $user->role))
                    ->orWhere(fn (Builder $userQuery) => $userQuery
                        ->where('target_type', 'user')
                        ->where('target_user_id', $user->id));

                if ($user->isStudent()) {
                    $classIds = $user->enrolledClasses()->pluck('classes.id');

                    $query->orWhere(fn (Builder $classQuery) => $classQuery
                        ->where('target_type', 'class')
                        ->whereIn('target_class_id', $classIds));
                }
            });
    }
}
