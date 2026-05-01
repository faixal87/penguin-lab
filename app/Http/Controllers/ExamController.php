<?php

namespace App\Http\Controllers;

use App\Models\StudentAnswer;
use App\Services\BadgeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ExamController extends Controller
{
    public function __construct(private BadgeService $badgeService)
    {
    }

    public function results(Request $request): View
    {
        $examSet = $this->assignedExamSet();
        $examSessionId = $this->examSessionId();

        $answers = StudentAnswer::with('scenario')
            ->where('user_id', $request->user()->id)
            ->where('exam_session_id', $examSessionId)
            ->orderBy('created_at')
            ->get();

        $totalScore = $answers->sum('score_awarded');
        $badge = $this->badgeService->forScore($totalScore);

        return view('exam.results', compact('answers', 'examSet', 'totalScore', 'badge'));
    }

    public function leaderboard(Request $request): View
    {
        $examSessionId = $this->examSessionId();
        $query = StudentAnswer::query()
            ->with('user')
            ->select('user_id', 'exam_session_id', 'set_no')
            ->selectRaw('SUM(score_awarded) as total_score')
            ->selectRaw('COUNT(*) as answered_count')
            ->whereNotNull('user_id');

        if ($request->user()->isLecturer()) {
            $studentIds = $request->user()
                ->teachingClasses()
                ->with('students:id')
                ->get()
                ->flatMap(fn ($class) => $class->students->pluck('id'))
                ->unique()
                ->values();

            $query->whereIn('user_id', $studentIds);
        }

        $scores = $query
            ->groupBy('user_id', 'exam_session_id', 'set_no')
            ->orderByDesc(DB::raw('SUM(score_awarded)'))
            ->limit(10)
            ->get();

        return view('leaderboard', [
            'scores' => $scores,
            'examSessionId' => $examSessionId,
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

    private function examSessionId(): string
    {
        if (! session()->has('exam_session_id')) {
            session(['exam_session_id' => (string) Str::uuid()]);
        }

        return (string) session('exam_session_id');
    }
}
