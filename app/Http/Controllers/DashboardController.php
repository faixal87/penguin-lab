<?php

namespace App\Http\Controllers;

use App\Models\FeedbackAnswer;
use App\Models\FeedbackQuestion;
use App\Models\SchoolClass;
use App\Models\Semester;
use App\Models\User;
use App\Services\BadgeService;
use App\Services\CourseFeedbackService;
use App\Services\ScoreService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private BadgeService $badgeService,
        private CourseFeedbackService $feedbackControls,
        private ScoreService $scores
    )
    {
    }

    public function __invoke(Request $request): View
    {
        $user = $request->user();

        if ($user->isAdmin()) {
            return view('admin.dashboard', [
                'users' => User::latest()->get(),
                'classes' => SchoolClass::with('lecturer', 'students')->latest('created_at')->get(),
                'examResults' => $this->scores->groupedExamResults(),
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
            $leaderboardQuery = User::whereIn('users.id', $studentIds)
                ->with('enrolledClasses.semester')
                ->where('role', 'student');
            $leaderboard = $this->scores->withTotalScore($leaderboardQuery)
                ->orderByDesc('total_score')
                ->orderBy('users.name')
                ->limit(10)
                ->get();

            return view('lecturer.dashboard', [
                'classes' => $classes,
                'currentSemester' => $currentSemester,
                'leaderboard' => $leaderboard,
                'badgeService' => $this->badgeService,
            ]);
        }

        $currentSemester = Semester::current();
        $totalScore = $this->scores->totalForUser($user);
        $latestAttempt = $this->scores->latestAttemptFor($user);
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
            $classStudents = $this->scores->attachTotalsToUsers($latestClass->students()->get())
                ->sortByDesc(fn (User $student) => (float) ($student->total_score ?? 0))
                ->values();

            $classSize = $classStudents->count();
            $classRank = $classStudents->search(fn (User $student) => $student->id === $user->id);
            $classRank = $classRank === false ? null : $classRank + 1;
            $topScorers = $classStudents->take(5);
        }

        if ($currentSemester) {
            $semesterLeaderboardQuery = User::where('role', 'student')
                ->whereHas('enrolledClasses', fn ($query) => $query->where('semester_id', $currentSemester->id))
                ->with('enrolledClasses.semester');
            $semesterLeaderboard = $this->scores->withTotalScore($semesterLeaderboardQuery)
                ->orderByDesc('total_score')
                ->orderBy('users.name')
                ->limit(5)
                ->get();
        }

        return view('dashboard', [
            'totalScore' => $totalScore,
            'latestAttempt' => $latestAttempt,
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
