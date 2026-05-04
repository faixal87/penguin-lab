<?php

namespace App\Http\Controllers;

use App\Models\QuestionSet;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LecturerQuestionSetAssignmentController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        return view('lecturer.question-set-assignment', [
            'classes' => $user->isAdmin()
                ? SchoolClass::with(['lecturer', 'questionSets.setQuestions.question', 'students.assignedQuestionSets'])->orderBy('class_name')->get()
                : $user->teachingClasses()->with(['questionSets.setQuestions.question', 'students.assignedQuestionSets'])->orderBy('class_name')->get(),
            'sets' => $this->visibleSets($request)->with('setQuestions.question')->orderBy('name')->get(),
            'students' => $user->isAdmin()
                ? User::where('role', 'student')->with('assignedQuestionSets', 'enrolledClasses')->orderBy('name')->get()
                : User::where('role', 'student')
                    ->whereHas('enrolledClasses', fn ($query) => $query->where('lecturer_id', $user->id))
                    ->with('assignedQuestionSets', 'enrolledClasses')
                    ->orderBy('name')
                    ->get(),
            'routePrefix' => $user->isAdmin() ? 'admin' : 'lecturer',
        ]);
    }

    public function update(Request $request, SchoolClass $schoolClass): RedirectResponse
    {
        abort_unless($schoolClass->lecturer_id === $request->user()->id || $request->user()->isAdmin(), 403);

        $data = $request->validate([
            'question_set_ids' => ['nullable', 'array'],
            'question_set_ids.*' => ['integer', 'exists:question_sets,id'],
        ]);

        $setIds = collect($data['question_set_ids'] ?? [])->map(fn ($id) => (int) $id)->unique()->values();
        $validSetIds = $this->validAssignableSetIds($request, $setIds);

        if ($setIds->count() !== $validSetIds->count()) {
            return back()->with('error', 'Only active question sets with exactly 100 marks can be assigned.');
        }

        $schoolClass->questionSets()->sync($validSetIds);
        $schoolClass->update(['question_set_id' => $validSetIds->first()]);

        return back()->with('status', 'Class question set assignment updated.');
    }

    public function updateStudent(Request $request, User $student): RedirectResponse
    {
        abort_unless($student->isStudent(), 404);
        abort_unless($request->user()->isAdmin() || $request->user()->teachingClasses()->whereHas('students', fn ($query) => $query->where('users.id', $student->id))->exists(), 403);

        $data = $request->validate([
            'question_set_ids' => ['nullable', 'array'],
            'question_set_ids.*' => ['integer', 'exists:question_sets,id'],
        ]);

        $setIds = collect($data['question_set_ids'] ?? [])->map(fn ($id) => (int) $id)->unique()->values();
        $validSetIds = $this->validAssignableSetIds($request, $setIds);

        if ($setIds->count() !== $validSetIds->count()) {
            return back()->with('error', 'Only active question sets with exactly 100 marks can be assigned.');
        }

        $student->assignedQuestionSets()->sync($validSetIds);

        return back()->with('status', 'Student question set assignment updated.');
    }

    private function visibleSets(Request $request)
    {
        return QuestionSet::query()
            ->when($request->user()->isLecturer(), function ($query) use ($request) {
                $query->where(function ($inner) use ($request) {
                    $inner->where('created_by', $request->user()->id)
                        ->orWhere(function ($shared) {
                            $shared->whereNull('created_by')->where('visibility', 'shared');
                        });
                });
            });
    }

    private function validAssignableSetIds(Request $request, $setIds)
    {
        if ($setIds->isEmpty()) {
            return collect();
        }

        return $this->visibleSets($request)
            ->whereIn('id', $setIds)
            ->with('setQuestions.question')
            ->get()
            ->filter(fn (QuestionSet $set) => $set->isAssignable())
            ->pluck('id')
            ->values();
    }
}
