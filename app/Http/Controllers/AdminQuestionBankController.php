<?php

namespace App\Http\Controllers;

use App\Models\QuestionBank;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminQuestionBankController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $questions = QuestionBank::query()
            ->when($user->isLecturer(), function ($query) use ($user) {
                $query->where(function ($inner) use ($user) {
                    $inner->where('created_by', $user->id)
                        ->orWhere(function ($shared) {
                            $shared->whereNull('created_by')
                                ->where('visibility', 'shared')
                                ->where('is_active', true);
                        });
                });
            })
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->query('search');

                $query->where(function ($inner) use ($search) {
                    $inner->where('title', 'like', '%' . $search . '%')
                        ->orWhere('category', 'like', '%' . $search . '%');
                });
            })
            ->orderBy('category')
            ->orderBy('title')
            ->paginate(20)
            ->withQueryString();

        return view('admin.question-bank.index', [
            'questions' => $questions,
            'routePrefix' => $this->routePrefix($request),
        ]);
    }

    public function create(Request $request): View
    {
        return view('admin.question-bank.form', [
            'question' => new QuestionBank(),
            'routePrefix' => $this->routePrefix($request),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        if ($request->user()->isLecturer()) {
            $data['created_by'] = $request->user()->id;
            $data['visibility'] = 'private';
        } else {
            $data['created_by'] = $data['created_by'] ?? null;
            $data['visibility'] = 'shared';
        }

        QuestionBank::create($data);

        return redirect()->route($this->routePrefix($request) . '.question-bank.index')->with('status', 'Question created.');
    }

    public function edit(Request $request, QuestionBank $question): View
    {
        abort_unless($this->canModify($request, $question), 403);

        return view('admin.question-bank.form', [
            'question' => $question,
            'routePrefix' => $this->routePrefix($request),
        ]);
    }

    public function update(Request $request, QuestionBank $question): RedirectResponse
    {
        abort_unless($this->canModify($request, $question), 403);

        $data = $this->validated($request);

        if ($request->user()->isLecturer()) {
            $data['created_by'] = $request->user()->id;
            $data['visibility'] = 'private';
        }

        $question->update($data);

        return redirect()->route($this->routePrefix($request) . '.question-bank.index')->with('status', 'Question updated.');
    }

    public function destroy(Request $request, QuestionBank $question): RedirectResponse
    {
        abort_unless($this->canModify($request, $question), 403);

        $question->delete();

        return back()->with('status', 'Question deleted.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'category' => ['nullable', 'string', 'max:255'],
            'difficulty' => ['nullable', 'string', 'max:255'],
            'score' => ['required', 'integer', 'min:1', 'max:100'],
            'expected_answer' => ['required', 'string'],
            'hint_1' => ['nullable', 'string'],
            'hint_2' => ['nullable', 'string'],
            'explanation' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]) + ['is_active' => false];
    }

    private function canModify(Request $request, QuestionBank $question): bool
    {
        return $request->user()->isAdmin()
            || ($request->user()->isLecturer() && (int) $question->created_by === $request->user()->id);
    }

    private function routePrefix(Request $request): string
    {
        return $request->user()->isLecturer() ? 'lecturer' : 'admin';
    }
}
