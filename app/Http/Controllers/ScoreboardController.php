<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Semester;
use App\Services\BadgeService;
use App\Services\ScoreService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ScoreboardController extends Controller
{
    public function __construct(
        private BadgeService $badgeService,
        private ScoreService $scores
    )
    {
    }

    public function index(Request $request): View
    {
        $sort = $request->query('sort', 'highest');
        $user = $request->user();
        $currentSemester = Semester::current();

        $students = User::query()
            ->where('role', 'student')
            ->whereHas('enrolledClasses', function (Builder $query) use ($user, $currentSemester) {
                if ($user->isLecturer()) {
                    $query->where('lecturer_id', $user->id);
                }
                if ($currentSemester) {
                    $query->where('semester_id', $currentSemester->id);
                }
            })
            ->with(['enrolledClasses' => function ($query) use ($user, $currentSemester) {
                if ($user->isLecturer()) {
                    $query->where('lecturer_id', $user->id);
                }
                if ($currentSemester) {
                    $query->where('semester_id', $currentSemester->id);
                }
                $query->with('semester');
            }]);

        $students = $this->scores->withTotalScore($students);

        match ($sort) {
            'lowest' => $students->orderBy('total_score')->orderBy('users.name'),
            'name' => $students->orderBy('users.name'),
            'matric_no' => $students->orderBy('users.matric_no')->orderBy('users.registration_no')->orderBy('users.name'),
            default => $students->orderByDesc('total_score')->orderBy('users.name'),
        };

        return view('scoreboard.index', [
            'students' => $students->get(),
            'sort' => $sort,
            'currentSemester' => $currentSemester,
            'badgeService' => $this->badgeService,
        ]);
    }
}
