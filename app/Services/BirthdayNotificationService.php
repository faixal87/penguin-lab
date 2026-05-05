<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use Carbon\CarbonInterface;

class BirthdayNotificationService
{
    public function __construct(private NotificationService $notifications)
    {
    }

    public function ensureFor(User $user): void
    {
        $birthday = $user->date_of_birth;

        if (! $birthday instanceof CarbonInterface) {
            return;
        }

        $today = now();

        if ((int) $birthday->month !== (int) $today->month || (int) $birthday->day !== (int) $today->day) {
            return;
        }

        $alreadySent = Notification::query()
            ->where('title', 'Birthday Greeting')
            ->where('target_type', 'user')
            ->where('target_user_id', $user->id)
            ->whereDate('created_at', $today->toDateString())
            ->exists();

        if ($alreadySent) {
            return;
        }

        $this->notifications->create([
            'title' => 'Birthday Greeting',
            'message' => "\u{1F382} Happy Birthday {$user->name}. Have a Blast!",
            'sender_id' => null,
            'target_type' => 'user',
            'target_role' => null,
            'target_class_id' => null,
            'target_user_id' => $user->id,
            'is_active' => true,
        ]);
    }
}
