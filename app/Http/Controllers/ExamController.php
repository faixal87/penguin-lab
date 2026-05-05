<?php

namespace App\Http\Controllers;

use App\Models\StudentAnswer;
use App\Models\User;
use App\Models\Semester;
use App\Services\BadgeService;
use App\Services\ScoreService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ExamController extends Controller
{
    public function __construct(
        private BadgeService $badgeService,
        private ScoreService $scores
    )
    {
    }

    public function results(Request $request): View
    {
        $examSessionId = $this->examSessionId();

        $answers = StudentAnswer::with(['scenario', 'question', 'setQuestion.questionSet'])
            ->where('user_id', $request->user()->id)
            ->where('exam_session_id', $examSessionId)
            ->orderBy('created_at')
            ->get();

        $totalScore = $answers->sum('score_awarded');
        $badge = $this->badgeService->forScore($totalScore);
        $examSet = $answers->pluck('setQuestion.questionSet.name')->filter()->unique()->join(', ') ?: 'No selected set';

        return view('exam.results', compact('answers', 'examSet', 'totalScore', 'badge'));
    }

    public function leaderboard(Request $request): View
    {
        $examSessionId = $this->examSessionId();
        $currentSemester = Semester::current();
        $studentQuery = User::query()
            ->where('role', 'student')
            ->whereHas('enrolledClasses', function ($query) use ($request, $currentSemester) {
                if ($request->user()->isLecturer()) {
                    $query->where('lecturer_id', $request->user()->id);
                }
                if ($currentSemester) {
                    $query->where('semester_id', $currentSemester->id);
                }
            })
            ->with(['enrolledClasses' => function ($query) use ($request, $currentSemester) {
                if ($request->user()->isLecturer()) {
                    $query->where('lecturer_id', $request->user()->id);
                }
                if ($currentSemester) {
                    $query->where('semester_id', $currentSemester->id);
                }
                $query->with('semester');
            }]);

        $scores = $this->scores->withTotalScore($studentQuery)
            ->withCount('studentAnswers as answered_count')
            ->orderByDesc('total_score')
            ->orderBy('users.name')
            ->limit(10)
            ->get();

        return view('leaderboard', [
            'scores' => $scores,
            'examSessionId' => $examSessionId,
            'currentSemester' => $currentSemester,
            'badgeService' => $this->badgeService,
        ]);
    }

    private function examSessionId(): string
    {
        if (! session()->has('exam_session_id')) {
            session(['exam_session_id' => (string) Str::uuid()]);
        }

        return (string) session('exam_session_id');
    }
}
