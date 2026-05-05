<?php

namespace App\Services;

use App\Models\QuestionSet;
use App\Models\StudentAnswer;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ScoreService
{
    public function withTotalScore(Builder $query): Builder
    {
        return $query
            ->leftJoinSub($this->totalsByUserQuery(), 'student_answer_totals', function ($join) {
                $join->on('users.id', '=', 'student_answer_totals.user_id');
            })
            ->select('users.*')
            ->selectRaw('COALESCE(student_answer_totals.total_score, 0) as total_score');
    }

    public function totalsByUserQuery()
    {
        return DB::table('student_answers')
            ->select('student_answers.user_id')
            ->selectRaw('SUM(student_answers.score_awarded) as total_score')
            ->whereNotNull('student_answers.user_id')
            ->groupBy('student_answers.user_id');
    }

    public function attachTotalsToUsers(Collection $users): Collection
    {
        $userIds = $users->pluck('id')->filter()->unique()->values();

        if ($userIds->isEmpty()) {
            return $users;
        }

        $totals = DB::table('student_answers')
            ->select('student_answers.user_id')
            ->selectRaw('SUM(student_answers.score_awarded) as total_score')
            ->whereIn('student_answers.user_id', $userIds)
            ->groupBy('student_answers.user_id')
            ->pluck('total_score', 'user_id');

        return $users->map(function (User $user) use ($totals) {
            $user->setAttribute('total_score', (float) ($totals[$user->id] ?? 0));

            return $user;
        });
    }

    public function totalForUser(User $user): float
    {
        return (float) DB::table('student_answers')
            ->where('user_id', $user->id)
            ->sum('score_awarded');
    }

    public function latestAttemptFor(User $user): ?array
    {
        $latest = StudentAnswer::query()
            ->where('user_id', $user->id)
            ->latest('created_at')
            ->latest('id')
            ->first();

        if (! $latest) {
            return null;
        }

        $query = StudentAnswer::query()->where('user_id', $user->id);
        $label = 'Question Set';

        if ($latest->question_set_id) {
            $query->where('question_set_id', $latest->question_set_id);
            $label = QuestionSet::whereKey($latest->question_set_id)->value('name') ?: "Question Set {$latest->question_set_id}";
        } else {
            $query->whereNull('question_set_id')->where('set_no', $latest->set_no);
            $label = $latest->set_no ? "Set {$latest->set_no}" : 'Legacy Set';
        }

        $summary = $query
            ->selectRaw('SUM(student_answers.score_awarded) as total_score, COUNT(*) as answered_count, MAX(student_answers.created_at) as latest_answered_at')
            ->first();

        return [
            'label' => $label,
            'question_set_id' => $latest->question_set_id,
            'set_no' => $latest->set_no,
            'total_score' => (float) ($summary->total_score ?? 0),
            'answered_count' => (int) ($summary->answered_count ?? 0),
            'latest_answered_at' => $summary->latest_answered_at ?? $latest->created_at,
        ];
    }

    public function groupedExamResults(): Collection
    {
        return StudentAnswer::query()
            ->with(['user', 'questionSet'])
            ->select('user_id', 'question_set_id', 'set_no')
            ->selectRaw('SUM(student_answers.score_awarded) as total_score')
            ->selectRaw('COUNT(*) as answered_count')
            ->selectRaw('MAX(student_answers.created_at) as latest_answered_at')
            ->groupBy('user_id', 'question_set_id', 'set_no')
            ->orderByDesc('latest_answered_at')
            ->get();
    }
}
