<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('all', fn ($user) => $user !== null);

Broadcast::channel('user.{id}', fn ($user, int $id) => (int) $user->id === $id);

Broadcast::channel('role.{role}', fn ($user, string $role) => $user->role === $role);

Broadcast::channel('class.{classId}', function ($user, int $classId) {
    if ($user->isStudent()) {
        return $user->enrolledClasses()->where('classes.id', $classId)->exists();
    }

    if ($user->isLecturer()) {
        return $user->teachingClasses()->whereKey($classId)->exists();
    }

    return $user->isAdmin();
});
