<?php

namespace App\Http\Controllers;

use App\Models\LoginLog;
use App\Models\FeedbackAnswer;
use App\Models\FeedbackQuestion;
use App\Models\Scenario;
use App\Models\SchoolClass;
use App\Models\StudentAnswer;
use App\Models\User;
use App\Services\BadgeService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private BadgeService $badgeService)
    {
    }

    public function __invoke(Request $request): View
    {
        $user = $request->user();

        if ($user->isAdmin()) {
            return view('admin.dashboard', [
                'users' => User::latest()->get(),
                'classes' => SchoolClass::with('lecturer', 'students')->latest('created_at')->get(),
                'loginLogs' => LoginLog::with('user')->latest('logged_in_at')->limit(50)->get(),
                'examResults' => StudentAnswer::with('user')
                    ->selectRaw('user_id, exam_session_id, set_no, SUM(score_awarded) as total_score, COUNT(*) as answered_count')
                    ->groupBy('user_id', 'exam_session_id', 'set_no')
                    ->orderByDesc('total_score')
                    ->get(),
                'badgeService' => $this->badgeService,
            ]);
        }

        if ($user->isLecturer()) {
            return view('lecturer.dashboard', [
                'classes' => $user->teachingClasses()->withCount('students')->latest('created_at')->get(),
            ]);
        }

        $totalScore = $user->studentAnswers()->sum('score_awarded');
        $examSet = $this->assignedExamSet();
        $scenarioIds = Scenario::where('set_no', $examSet)->pluck('id');
        $totalModules = $scenarioIds->count();
        $completedModules = $totalModules > 0
            ? $user->studentAnswers()->whereIn('scenario_id', $scenarioIds)->distinct('scenario_id')->count('scenario_id')
            : 0;
        $completionPercentage = $totalModules > 0 ? round(($completedModules / $totalModules) * 100) : 0;
        $hasSubmittedFeedback = FeedbackAnswer::where('user_id', $user->id)->exists();
        $hasActiveFeedbackQuestions = FeedbackQuestion::where('is_active', true)->exists();
        $shouldShowFeedbackPrompt = $hasActiveFeedbackQuestions && $totalModules > 0 && $completedModules >= $totalModules && ! $hasSubmittedFeedback;
        $latestClass = $user->enrolledClasses()->orderByDesc('class_student.id')->first();
        $terminalEnabled = (bool) $user->terminal_enabled || $user->enrolledClasses()
            ->where('terminal_enabled', true)
            ->exists();
        $terminalRunning = $terminalEnabled && $user->container_status === 'running';
        $classRank = null;
        $classSize = 0;
        $topScorers = collect();

        if ($latestClass) {
            $classStudents = $latestClass->students()
                ->withSum('studentAnswers as total_score', 'score_awarded')
                ->get()
                ->sortByDesc(fn (User $student) => (float) ($student->total_score ?? 0))
                ->values();

            $classSize = $classStudents->count();
            $classRank = $classStudents->search(fn (User $student) => $student->id === $user->id);
            $classRank = $classRank === false ? null : $classRank + 1;
            $topScorers = $classStudents->take(5);
        }

        return view('dashboard', [
            'totalScore' => $totalScore,
            'answeredCount' => $user->studentAnswers()->count(),
            'badge' => $this->badgeService->forScore($totalScore),
            'latestClass' => $latestClass,
            'terminalEnabled' => $terminalEnabled,
            'terminalRunning' => $terminalRunning,
            'classRank' => $classRank,
            'classSize' => $classSize,
            'topScorers' => $topScorers,
            'completedModules' => $completedModules,
            'totalModules' => $totalModules,
            'completionPercentage' => $completionPercentage,
            'hasSubmittedFeedback' => $hasSubmittedFeedback,
            'shouldShowFeedbackPrompt' => $shouldShowFeedbackPrompt,
            'badgeService' => $this->badgeService,
        ]);
    }

    private function assignedExamSet(): int
    {
        if (! session()->has('exam_set')) {
            session(['exam_set' => random_int(1, 10)]);
        }

        return (int) session('exam_set');
    }
}
