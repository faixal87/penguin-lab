<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\User;
use App\Services\CourseFeedbackService;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function __construct(
        private NotificationService $notifications,
        private CourseFeedbackService $feedbackControls
    ) {
    }

    public function index(Request $request): View
    {
        return view('notifications.index', [
            'notifications' => $this->notifications->visibleFor($request->user()),
        ]);
    }

    public function unreadJson(Request $request): JsonResponse
    {
        $notifications = $this->notifications->unreadUndismissedFor($request->user());

        return response()->json([
            'unread_count' => $this->notifications->unreadCount($request->user()),
            'notifications' => $notifications->map(fn (Notification $notification) => [
                'id' => $notification->id,
                'title' => $notification->title,
                'message' => $notification->message,
                'created_at' => $notification->created_at?->format('Y-m-d H:i'),
                'read_url' => route('notifications.read', $notification),
                'dismiss_url' => route('notifications.dismiss', $notification),
                'feedback_url' => route('feedback.form'),
            ])->values(),
        ]);
    }

    public function manage(Request $request): View
    {
        abort_unless($request->user()->isAdmin() || $request->user()->isLecturer(), 403);

        $user = $request->user();

        return view('notifications.manage', [
            'notifications' => Notification::query()
                ->with(['sender', 'targetClass', 'targetUser'])
                ->when($user->isLecturer(), fn ($query) => $query->where('sender_id', $user->id))
                ->latest('created_at')
                ->get(),
            'classes' => $this->feedbackControls->classesFor($user),
            'users' => $user->isAdmin()
                ? User::orderBy('name')->get()
                : $this->feedbackControls->lecturerStudents($user),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->isAdmin() || $request->user()->isLecturer(), 403);

        $validated = $this->validatedNotification($request);

        $this->notifications->create($validated + [
            'sender_id' => $request->user()->id,
            'is_active' => true,
        ]);

        return back()->with('status', 'Notification created.');
    }

    public function markRead(Request $request, Notification $notification): RedirectResponse
    {
        abort_unless($this->notifications->canSee($notification, $request->user()), 403);

        $this->notifications->markRead($notification, $request->user());

        return back()->with('status', 'Notification marked as read.');
    }

    public function dismiss(Request $request, Notification $notification): RedirectResponse|JsonResponse
    {
        abort_unless($this->notifications->canSee($notification, $request->user()), 403);

        $this->notifications->dismiss($notification, $request->user());

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'unread_count' => $this->notifications->unreadCount($request->user()),
            ]);
        }

        return back();
    }

    public function toggle(Request $request, Notification $notification): RedirectResponse
    {
        abort_unless($this->notifications->canManage($notification, $request->user()), 403);

        $notification->update(['is_active' => ! $notification->is_active]);

        return back()->with('status', 'Notification status updated.');
    }

    public function destroy(Request $request, Notification $notification): RedirectResponse
    {
        abort_unless($this->notifications->canManage($notification, $request->user()), 403);

        $notification->reads()->delete();
        $notification->delete();

        return back()->with('status', 'Notification deleted.');
    }

    public function batchDestroy(Request $request): RedirectResponse
    {
        abort_unless($request->user()->isAdmin() || $request->user()->isLecturer(), 403);

        $validated = $request->validate([
            'notification_ids' => ['required', 'array', 'min:1'],
            'notification_ids.*' => ['integer', 'exists:notifications,id'],
        ]);

        $deleted = 0;
        $skipped = 0;

        Notification::whereIn('id', $validated['notification_ids'])->get()->each(function (Notification $notification) use ($request, &$deleted, &$skipped) {
            if (! $this->notifications->canManage($notification, $request->user())) {
                $skipped++;
                return;
            }

            $notification->reads()->delete();
            $notification->delete();
            $deleted++;
        });

        return back()->with('status', "Notification delete complete. Deleted: {$deleted}. Skipped: {$skipped}.");
    }

    private function validatedNotification(Request $request): array
    {
        $user = $request->user();
        $targetTypes = $user->isAdmin() ? ['all', 'role', 'class', 'user'] : ['class', 'user'];

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string'],
            'target_type' => ['required', Rule::in($targetTypes)],
            'target_role' => ['nullable', Rule::in(['admin', 'lecturer', 'student'])],
            'target_class_id' => ['nullable', 'integer', 'exists:classes,id'],
            'target_user_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $validated['target_role'] = $validated['target_type'] === 'role' ? $validated['target_role'] : null;
        $validated['target_class_id'] = $validated['target_type'] === 'class' ? $validated['target_class_id'] : null;
        $validated['target_user_id'] = $validated['target_type'] === 'user' ? $validated['target_user_id'] : null;

        if ($validated['target_type'] === 'role' && ! $validated['target_role']) {
            abort(422, 'Select a target role.');
        }

        if ($validated['target_type'] === 'class') {
            abort_unless($validated['target_class_id'], 422);
            abort_if($user->isLecturer() && ! $user->teachingClasses()->whereKey($validated['target_class_id'])->exists(), 403);
        }

        if ($validated['target_type'] === 'user') {
            abort_unless($validated['target_user_id'], 422);
            abort_if($user->isLecturer() && ! $user->teachingClasses()->whereHas('students', fn ($query) => $query->where('users.id', $validated['target_user_id']))->exists(), 403);
        }

        return $validated;
    }
}
