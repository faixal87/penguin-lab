<?php

namespace App\Http\Controllers;

use App\Models\SchoolClass;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\TerminalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TerminalController extends Controller
{
    public function __construct(
        private TerminalService $terminalService,
        private ActivityLogger $activityLogger
    )
    {
    }

    public function index(Request $request): View
    {
        $user = $request->user();
        abort_unless($user->isStudent(), 403);

        $enabledByClass = $user->enrolledClasses()
            ->where('terminal_enabled', true)
            ->exists();

        return view('terminal.index', [
            'terminalEnabled' => (bool) $user->terminal_enabled || $enabledByClass,
            'enabledByUser' => (bool) $user->terminal_enabled,
            'enabledByClass' => $enabledByClass,
            'linuxUsername' => $this->terminalService->generateLinuxUsername($user),
            'guacamoleUsername' => $this->terminalService->guacamoleUsername($user),
            'containerStatus' => $user->container_status ?: 'not_started',
            'automationEnabled' => $this->terminalService->automationEnabled(),
            'guacamoleBaseUrl' => $this->terminalService->getGuacamoleConnectionUrl($user) ?: (config('services.guacamole.base_url', '#') ?: '#'),
            'guacamoleMode' => config('services.guacamole.mode', 'placeholder'),
        ]);
    }

    public function launch(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->isStudent(), 403);

        $enabledByClass = $user->enrolledClasses()
            ->where('terminal_enabled', true)
            ->exists();

        abort_unless((bool) $user->terminal_enabled || $enabledByClass, 403);

        return back()->with('status', 'Use the Launch Terminal button to open Apache Guacamole in a new tab.');
    }

    public function stop(Request $request): RedirectResponse
    {
        abort_unless($request->user()->isStudent(), 403);

        return back()->with('status', 'Terminal stop is reserved for a later Docker integration phase.');
    }

    public function adminSettings(Request $request): View|RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);

        if ($request->isMethod('post')) {
            $validated = $request->validate([
                'action' => ['required', 'in:enable_all_students,disable_all_students'],
            ]);

            User::where('role', 'student')->update([
                'terminal_enabled' => $validated['action'] === 'enable_all_students',
            ]);

            return back()->with('status', 'Student terminal access updated globally.');
        }

        $students = User::where('role', 'student')
            ->with('enrolledClasses')
            ->orderBy('name')
            ->get();

        return view('terminal.admin-settings', [
            'enabledCount' => User::where('role', 'student')->where('terminal_enabled', true)->count(),
            'studentCount' => User::where('role', 'student')->count(),
            'students' => $students,
            'automationEnabled' => $this->terminalService->automationEnabled(),
            'commandPreview' => $request->session()->get('terminal_command_preview'),
        ]);
    }

    public function previewCommand(Request $request, User $user): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);
        abort_unless($user->isStudent(), 403);

        $validated = $request->validate([
            'command_type' => ['required', 'in:start,stop'],
        ]);

        $preview = $validated['command_type'] === 'start'
            ? $this->terminalService->previewStartTerminal($user)
            : $this->terminalService->previewStopTerminal($user);

        return back()->with('terminal_command_preview', [
            'student' => $user->name,
            'type' => $validated['command_type'],
            'command' => $preview['command'],
            'message' => $preview['message'],
            'executed' => $preview['executed'],
            'success' => $preview['success'],
            'output' => $preview['output'],
        ]);
    }

    public function runCommand(Request $request, User $user): RedirectResponse
    {
        $this->authorizeTerminalUser($request, $user);

        $validated = $request->validate([
            'command_type' => ['required', 'in:start,stop'],
        ]);

        if ($validated['command_type'] === 'start' && $user->container_status === 'initializing') {
            return back()->with('error', 'Terminal is still initializing. Please wait until SSH is ready.');
        }

        $result = $validated['command_type'] === 'start'
            ? $this->terminalService->startTerminal($user)
            : $this->terminalService->stopTerminal($user);

        if ($result['success'] && $result['executed']) {
            $this->activityLogger->log(
                $user,
                $validated['command_type'] === 'start' ? 'start terminal' : 'stop terminal',
                $request
            );
        }

        $sessionPayload = [
            'student' => $user->name,
            'type' => $validated['command_type'],
            'command' => $result['command'],
            'message' => $result['message'],
            'executed' => $result['executed'],
            'success' => $result['success'],
            'output' => $request->user()->isAdmin() ? $result['output'] : '',
        ];

        return back()
            ->with('terminal_command_preview', $sessionPayload)
            ->with($result['success'] ? 'status' : 'error', $result['message']);
    }

    public function syncGuacamole(Request $request, User $user): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);
        abort_unless($user->isStudent(), 403);

        if ($user->container_status === 'initializing') {
            return back()->with('error', 'Terminal is still initializing. Please sync Guacamole after SSH is ready.');
        }

        $result = $this->terminalService->createOrUpdateGuacamoleConnection($user);

        return back()->with($result['success'] ? 'status' : 'error', $result['message']);
    }

    public function resetGuacamolePassword(Request $request, User $user): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);
        abort_unless($user->isStudent(), 403);

        $result = $this->terminalService->resetGuacamolePassword($user);

        return back()->with($result['success'] ? 'status' : 'error', $result['message']);
    }

    public function lecturerSettings(Request $request): View|RedirectResponse
    {
        abort_unless($request->user()->isAdmin() || $request->user()->isLecturer(), 403);

        if ($request->isMethod('get')) {
            abort_unless($request->user()->isLecturer(), 403);

            return view('terminal.lecturer-settings', [
                'classes' => $request->user()
                    ->teachingClasses()
                    ->withCount('students')
                    ->latest('created_at')
                    ->get(),
            ]);
        }

        $validated = $request->validate([
            'class_id' => ['required', 'exists:classes,id'],
            'terminal_enabled' => ['nullable', 'boolean'],
        ]);

        $class = SchoolClass::findOrFail($validated['class_id']);

        abort_unless(
            $request->user()->isAdmin() || $class->lecturer_id === $request->user()->id,
            403
        );

        $class->update([
            'terminal_enabled' => $request->boolean('terminal_enabled'),
        ]);

        return back()->with('status', 'Class terminal access updated.');
    }

    private function authorizeTerminalUser(Request $request, User $student): void
    {
        abort_unless($student->isStudent(), 403);

        if ($request->user()->isAdmin()) {
            return;
        }

        abort_unless($request->user()->isLecturer(), 403);

        $canManage = $request->user()
            ->teachingClasses()
            ->where('terminal_enabled', true)
            ->whereHas('students', fn ($query) => $query->where('users.id', $student->id))
            ->exists();

        abort_unless($canManage, 403);
    }
}
