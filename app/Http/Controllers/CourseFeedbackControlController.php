<?php

namespace App\Http\Controllers;

use App\Models\CourseFeedbackControl;
use App\Services\CourseFeedbackService;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CourseFeedbackControlController extends Controller
{
    public function __construct(
        private CourseFeedbackService $feedbackControls,
        private NotificationService $notifications
    ) {
    }

    public function index(Request $request): View
    {
        abort_unless($request->user()->isAdmin() || $request->user()->isLecturer(), 403);

        $user = $request->user();

        return view('feedback.control', [
            'controls' => $this->feedbackControls->manageableControls($user),
            'classes' => $this->feedbackControls->classesFor($user),
            'users' => $user->isAdmin()
                ? \App\Models\User::where('role', 'student')->orderBy('name')->get()
                : $this->feedbackControls->lecturerStudents($user),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->isAdmin() || $request->user()->isLecturer(), 403);

        $validated = $this->validatedControl($request);
        $enabled = $request->boolean('is_enabled');

        $control = CourseFeedbackControl::updateOrCreate(
            [
                'target_type' => $validated['target_type'],
                'target_role' => $validated['target_role'],
                'target_class_id' => $validated['target_class_id'],
                'target_user_id' => $validated['target_user_id'],
            ],
            [
                'created_by' => $request->user()->id,
                'is_enabled' => $enabled,
            ]
        );

        if ($enabled) {
            $this->notifications->create($this->feedbackNotificationPayload($request, $control));
        }

        return back()->with('status', 'Course feedback access updated.');
    }

    public function toggle(Request $request, CourseFeedbackControl $control): RedirectResponse
    {
        abort_unless($request->user()->isAdmin() || $request->user()->isLecturer(), 403);
        $this->authorizeControl($request, $control);

        $control->update(['is_enabled' => ! $control->is_enabled]);

        if ($control->is_enabled) {
            $this->notifications->create($this->feedbackNotificationPayload($request, $control));
        }

        return back()->with('status', 'Course feedback access updated.');
    }

    private function feedbackNotificationPayload(Request $request, CourseFeedbackControl $control): array
    {
        return [
                'title' => 'Course Feedback is now available',
                'message' => 'Course Feedback is now available. Please open the feedback form when you are ready to submit your response.',
                'sender_id' => $request->user()->id,
                'target_type' => $control->target_type === 'all' ? 'role' : $control->target_type,
                'target_role' => $control->target_type === 'all' ? 'student' : $control->target_role,
                'target_class_id' => $control->target_class_id,
                'target_user_id' => $control->target_user_id,
                'is_active' => true,
        ];
    }

    private function validatedControl(Request $request): array
    {
        $user = $request->user();
        $targetTypes = $user->isAdmin() ? ['all', 'role', 'class', 'user'] : ['class', 'user'];

        $validated = $request->validate([
            'target_type' => ['required', Rule::in($targetTypes)],
            'target_role' => ['nullable', Rule::in(['student'])],
            'target_class_id' => ['nullable', 'integer', 'exists:classes,id'],
            'target_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'is_enabled' => ['nullable', 'boolean'],
        ]);

        $validated['target_role'] = $validated['target_type'] === 'role' ? ($validated['target_role'] ?: 'student') : null;
        $validated['target_class_id'] = $validated['target_type'] === 'class' ? $validated['target_class_id'] : null;
        $validated['target_user_id'] = $validated['target_type'] === 'user' ? $validated['target_user_id'] : null;

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

    private function authorizeControl(Request $request, CourseFeedbackControl $control): void
    {
        if ($request->user()->isAdmin()) {
            return;
        }

        abort_unless($request->user()->isLecturer(), 403);

        if ($control->target_type === 'class') {
            abort_unless($request->user()->teachingClasses()->whereKey($control->target_class_id)->exists(), 403);
            return;
        }

        if ($control->target_type === 'user') {
            abort_unless($request->user()->teachingClasses()->whereHas('students', fn ($query) => $query->where('users.id', $control->target_user_id))->exists(), 403);
            return;
        }

        abort(403);
    }
}
