<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\BadgeService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ScoreboardController extends Controller
{
    public function __construct(private BadgeService $badgeService)
    {
    }

    public function index(Request $request): View
    {
        $sort = $request->query('sort', 'highest');
        $user = $request->user();

        $students = User::query()
            ->where('role', 'student')
            ->whereHas('enrolledClasses', function (Builder $query) use ($user) {
                if ($user->isLecturer()) {
                    $query->where('lecturer_id', $user->id);
                }
            })
            ->with(['enrolledClasses' => function ($query) use ($user) {
                if ($user->isLecturer()) {
                    $query->where('lecturer_id', $user->id);
                }
            }])
            ->withSum('studentAnswers as total_score', 'score_awarded');

        match ($sort) {
            'lowest' => $students->orderBy('total_score')->orderBy('name'),
            'name' => $students->orderBy('name'),
            'matric_no' => $students->orderBy('matric_no')->orderBy('registration_no')->orderBy('name'),
            default => $students->orderByDesc('total_score')->orderBy('name'),
        };

        return view('scoreboard.index', [
            'students' => $students->get(),
            'sort' => $sort,
            'badgeService' => $this->badgeService,
        ]);
    }
}
