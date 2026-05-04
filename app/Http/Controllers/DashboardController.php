<?php

namespace App\Http\Controllers;

use App\Models\LoginLog;
use App\Models\FeedbackAnswer;
use App\Models\FeedbackQuestion;
use App\Models\SchoolClass;
use App\Models\Semester;
use App\Models\StudentAnswer;
use App\Models\User;
use App\Services\BadgeService;
use App\Services\CourseFeedbackService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private BadgeService $badgeService,
        private CourseFeedbackService $feedbackControls
    )
    {
    }

    public function __invoke(Request $request): View
    {
        $user = $request->user();

        if ($user->isAdmin()) {
            $loginLogPerPage = (int) $request->query('login_log_per_page', 10);
            $loginLogPerPage = in_array($loginLogPerPage, [10, 20, 50], true) ? $loginLogPerPage : 10;
            $loginLogSearch = trim((string) $request->query('login_log_search', ''));

            return view('admin.dashboard', [
                'users' => User::latest()->get(),
                'classes' => SchoolClass::with('lecturer', 'students')->latest('created_at')->get(),
                'loginLogs' => LoginLog::query()
                    ->with('user')
                    ->when($loginLogSearch !== '', function ($query) use ($loginLogSearch) {
                        $search = strtolower($loginLogSearch);

                        $query->where(function ($inner) use ($search) {
                            $inner->whereRaw('LOWER(ip_address) LIKE ?', ["%{$search}%"])
                                ->orWhereRaw('LOWER(browser) LIKE ?', ["%{$search}%"])
                                ->orWhereRaw('LOWER(user_agent) LIKE ?', ["%{$search}%"])
                                ->orWhereHas('user', function ($userQuery) use ($search) {
                                    $userQuery->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"])
                                        ->orWhereRaw('LOWER(email) LIKE ?', ["%{$search}%"]);
                                });
                        });
                    })
                    ->latest('logged_in_at')
                    ->paginate($loginLogPerPage, ['*'], 'login_logs_page')
                    ->withQueryString(),
                'loginLogSearch' => $loginLogSearch,
                'loginLogPerPage' => $loginLogPerPage,
                'examResults' => StudentAnswer::with('user')
                    ->selectRaw('user_id, exam_session_id, set_no, SUM(score_awarded) as total_score, COUNT(*) as answered_count')
                    ->groupBy('user_id', 'exam_session_id', 'set_no')
                    ->orderByDesc('total_score')
                    ->get(),
                'badgeService' => $this->badgeService,
                'currentSemester' => Semester::current(),
            ]);
        }

        if ($user->isLecturer()) {
            $currentSemester = Semester::current();
            $classes = $user->teachingClasses()
                ->with('semester')
                ->withCount('students')
                ->when($currentSemester, fn ($query) => $query->where('semester_id', $currentSemester->id))
                ->latest('created_at')
                ->get();
            $studentIds = $user->teachingClasses()
                ->when($currentSemester, fn ($query) => $query->where('semester_id', $currentSemester->id))
                ->with('students:id')
                ->get()
                ->flatMap(fn ($class) => $class->students->pluck('id'))
                ->unique()
                ->values();
            $leaderboard = User::whereIn('id', $studentIds)
                ->with('enrolledClasses.semester')
                ->withSum('studentAnswers as total_score', 'score_awarded')
                ->get()
                ->sortByDesc(fn (User $student) => (float) ($student->total_score ?? 0))
                ->take(10)
                ->values();

            return view('lecturer.dashboard', [
                'classes' => $classes,
                'currentSemester' => $currentSemester,
                'leaderboard' => $leaderboard,
                'badgeService' => $this->badgeService,
            ]);
        }

        $currentSemester = Semester::current();
        $totalScore = $user->studentAnswers()->sum('score_awarded');
        $latestClass = $user->enrolledClasses()
            ->with('questionSets.setQuestions', 'semester')
            ->when($currentSemester, fn ($query) => $query->where('semester_id', $currentSemester->id))
            ->orderByDesc('class_student.id')
            ->first();
        $studentAssignedSets = $user->assignedQuestionSets()->where('is_active', true)->with('setQuestions')->get()->filter(fn ($set) => $set->isAssignable())->values();
        $assignedQuestionSets = $studentAssignedSets->isNotEmpty()
            ? $studentAssignedSets
            : ($latestClass?->questionSets?->filter(fn ($set) => $set->is_active && $set->isAssignable())->values() ?? collect());

        if ($assignedQuestionSets->isNotEmpty()) {
            $setQuestionIds = $assignedQuestionSets->flatMap(fn ($set) => $set->setQuestions->pluck('id'))->unique()->values();
            $totalModules = $setQuestionIds->count();
            $completedModules = $totalModules > 0
                ? $user->studentAnswers()->whereIn('question_set_question_id', $setQuestionIds)->distinct('question_set_question_id')->count('question_set_question_id')
                : 0;
        } else {
            $totalModules = 0;
            $completedModules = 0;
        }
        $completionPercentage = $totalModules > 0 ? round(($completedModules / $totalModules) * 100) : 0;
        $hasSubmittedFeedback = FeedbackAnswer::where('user_id', $user->id)->exists();
        $hasActiveFeedbackQuestions = FeedbackQuestion::where('is_active', true)->exists();
        $feedbackEnabled = $this->feedbackControls->isEnabledFor($user);
        $shouldShowFeedbackPrompt = $feedbackEnabled && $hasActiveFeedbackQuestions && ! $hasSubmittedFeedback;
        $terminalEnabled = (bool) $user->terminal_enabled || $user->enrolledClasses()
            ->where('terminal_enabled', true)
            ->exists();
        $terminalRunning = $terminalEnabled && $user->container_status === 'running';
        $classRank = null;
        $classSize = 0;
        $topScorers = collect();
        $semesterLeaderboard = collect();

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

        if ($currentSemester) {
            $semesterLeaderboard = User::where('role', 'student')
                ->whereHas('enrolledClasses', fn ($query) => $query->where('semester_id', $currentSemester->id))
                ->with('enrolledClasses.semester')
                ->withSum('studentAnswers as total_score', 'score_awarded')
                ->get()
                ->sortByDesc(fn (User $student) => (float) ($student->total_score ?? 0))
                ->take(5)
                ->values();
        }

        return view('dashboard', [
            'totalScore' => $totalScore,
            'answeredCount' => $user->studentAnswers()->count(),
            'badge' => $this->badgeService->forScore($totalScore),
            'latestClass' => $latestClass,
            'assignedQuestionSets' => $assignedQuestionSets,
            'terminalEnabled' => $terminalEnabled,
            'terminalRunning' => $terminalRunning,
            'classRank' => $classRank,
            'classSize' => $classSize,
            'topScorers' => $topScorers,
            'semesterLeaderboard' => $semesterLeaderboard,
            'completedModules' => $completedModules,
            'totalModules' => $totalModules,
            'completionPercentage' => $completionPercentage,
            'hasSubmittedFeedback' => $hasSubmittedFeedback,
            'shouldShowFeedbackPrompt' => $shouldShowFeedbackPrompt,
            'badgeService' => $this->badgeService,
            'currentSemester' => $currentSemester,
        ]);
    }

}
