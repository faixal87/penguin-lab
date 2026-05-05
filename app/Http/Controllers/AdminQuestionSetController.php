<?php

namespace App\Http\Controllers;

use App\Models\QuestionBank;
use App\Models\QuestionSet;
use App\Models\QuestionSetQuestion;
use Illuminate\Support\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminQuestionSetController extends Controller
{
    public function index(Request $request): View
    {
        return view('admin.question-sets.index', [
            'sets' => $this->manageableSets($request)
                ->with(['setQuestions.question', 'classes', 'legacyClasses'])
                ->latest()
                ->get(),
            'routePrefix' => $this->routePrefix($request),
        ]);
    }

    public function create(Request $request): View
    {
        return view('admin.question-sets.form', [
            'set' => new QuestionSet(),
            'routePrefix' => $this->routePrefix($request),
            'questions' => collect(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedSet($request) + ['is_active' => false];

        if ($request->user()->isLecturer()) {
            $data['created_by'] = $request->user()->id;
            $data['visibility'] = 'private';
        } else {
            $data['visibility'] = 'shared';
        }

        $set = QuestionSet::create($data);

        return redirect()->route($this->routePrefix($request) . '.question-sets.edit', $set)->with('status', 'Question set created.');
    }

    public function edit(Request $request, QuestionSet $set): View
    {
        abort_unless($this->canManage($request, $set), 403);

        return view('admin.question-sets.form', [
            'set' => $set->load('setQuestions.question', 'classes', 'legacyClasses'),
            'questions' => $this->visibleQuestions($request)->where('is_active', true)->orderBy('category')->orderBy('title')->get(),
            'routePrefix' => $this->routePrefix($request),
            'assignedClasses' => $this->assignedClasses($set),
        ]);
    }

    public function update(Request $request, QuestionSet $set): RedirectResponse
    {
        abort_unless($this->canManage($request, $set), 403);

        $data = $this->validatedSet($request);
        $wantsActive = $request->boolean('is_active');

        if ($wantsActive && $set->load('setQuestions.question')->currentTotal() !== 100) {
            return back()->with('error', 'Question set total must be exactly 100 marks before activation.');
        }

        if ($request->user()->isLecturer()) {
            $data['created_by'] = $request->user()->id;
            $data['visibility'] = 'private';
        }

        $set->update($data + ['is_active' => $wantsActive]);

        return back()->with('status', 'Question set updated.');
    }

    public function destroy(Request $request, QuestionSet $set): RedirectResponse
    {
        abort_unless($this->canManage($request, $set), 403);

        $assignedClasses = $this->assignedClasses($set);

        if ($assignedClasses->isNotEmpty()) {
            return back()->with(
                'error',
                'This question set is assigned to class(es): ' . $assignedClasses->pluck('class_name')->join(', ') . '. Remove the assignment before deleting.'
            );
        }

        $set->delete();

        return back()->with('status', 'Question set deleted.');
    }

    public function addQuestion(Request $request, QuestionSet $set): RedirectResponse
    {
        abort_unless($this->canManage($request, $set), 403);

        $data = $request->validate([
            'question_bank_id' => ['required', 'exists:question_bank,id'],
            'mark' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        abort_unless($this->visibleQuestions($request)->whereKey($data['question_bank_id'])->exists(), 403);

        QuestionSetQuestion::create([
            'question_set_id' => $set->id,
            'question_bank_id' => $data['question_bank_id'],
            'mark' => $data['mark'] ?? null,
            'sort_order' => ((int) $set->setQuestions()->max('sort_order')) + 1,
        ]);

        return back()->with('status', 'Question added to set.');
    }

    public function createQuestion(Request $request, QuestionSet $set): RedirectResponse
    {
        abort_unless($this->canManage($request, $set), 403);

        $questionData = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'category' => ['nullable', 'string', 'max:255'],
            'difficulty' => ['nullable', 'string', 'max:255'],
            'score' => ['required', 'integer', 'min:1', 'max:100'],
            'expected_answer' => ['required', 'string'],
            'hint_1' => ['nullable', 'string'],
            'hint_2' => ['nullable', 'string'],
            'explanation' => ['nullable', 'string'],
        ]) + ['is_active' => true];

        if ($request->user()->isLecturer()) {
            $questionData['created_by'] = $request->user()->id;
            $questionData['visibility'] = 'private';
        } else {
            $questionData['visibility'] = 'shared';
        }

        $question = QuestionBank::create($questionData);

        QuestionSetQuestion::create([
            'question_set_id' => $set->id,
            'question_bank_id' => $question->id,
            'mark' => $question->score,
            'sort_order' => ((int) $set->setQuestions()->max('sort_order')) + 1,
        ]);

        return back()->with('status', 'Custom question created and added.');
    }

    public function updateQuestion(Request $request, QuestionSetQuestion $setQuestion): RedirectResponse
    {
        abort_unless($this->canManage($request, $setQuestion->questionSet), 403);

        $setQuestion->update($request->validate([
            'mark' => ['nullable', 'integer', 'min:1', 'max:100'],
            'sort_order' => ['required', 'integer', 'min:0'],
        ]));

        return back()->with('status', 'Set question updated.');
    }

    public function removeQuestion(Request $request, QuestionSetQuestion $setQuestion): RedirectResponse
    {
        abort_unless($this->canManage($request, $setQuestion->questionSet), 403);

        $setQuestion->delete();

        return back()->with('status', 'Question removed from set.');
    }

    private function validatedSet(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'total_marks' => ['required', 'integer', 'min:1'],
        ]);
    }

    private function manageableSets(Request $request)
    {
        return QuestionSet::query()
            ->when($request->user()->isLecturer(), fn ($query) => $query->where('created_by', $request->user()->id));
    }

    private function visibleQuestions(Request $request)
    {
        return QuestionBank::query()
            ->when($request->user()->isLecturer(), function ($query) use ($request) {
                $query->where(function ($inner) use ($request) {
                    $inner->where('created_by', $request->user()->id)
                        ->orWhere(function ($shared) {
                            $shared->whereNull('created_by')
                                ->where('visibility', 'shared')
                                ->where('is_active', true);
                        });
                });
            });
    }

    private function canManage(Request $request, QuestionSet $set): bool
    {
        return $request->user()->isAdmin()
            || ($request->user()->isLecturer() && (int) $set->created_by === $request->user()->id);
    }

    private function assignedClasses(QuestionSet $set): Collection
    {
        $set->loadMissing('classes', 'legacyClasses');

        return $set->classes
            ->merge($set->legacyClasses)
            ->unique('id')
            ->values();
    }

    private function routePrefix(Request $request): string
    {
        return $request->user()->isLecturer() ? 'lecturer' : 'admin';
    }
}
