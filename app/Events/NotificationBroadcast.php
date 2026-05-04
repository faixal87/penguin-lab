<?php

namespace App\Events;

use App\Models\Notification;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NotificationBroadcast implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Notification $notification)
    {
    }

    public function broadcastOn(): array
    {
        return match ($this->notification->target_type) {
            'all' => [new PrivateChannel('all')],
            'role' => [new PrivateChannel('role.' . $this->notification->target_role)],
            'class' => [new PrivateChannel('class.' . $this->notification->target_class_id)],
            'user' => [new PrivateChannel('user.' . $this->notification->target_user_id)],
            default => [],
        };
    }

    public function broadcastAs(): string
    {
        return 'notification.created';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->notification->id,
            'title' => $this->notification->title,
            'message' => $this->notification->message,
            'target_type' => $this->notification->target_type,
            'target_role' => $this->notification->target_role,
            'target_class_id' => $this->notification->target_class_id,
            'target_user_id' => $this->notification->target_user_id,
            'created_at' => $this->notification->created_at?->format('Y-m-d H:i'),
            'read_url' => route('notifications.read', $this->notification),
            'dismiss_url' => route('notifications.dismiss', $this->notification),
            'feedback_url' => route('feedback.form'),
        ];
    }
}
