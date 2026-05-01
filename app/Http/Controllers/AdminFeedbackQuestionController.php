<?php

namespace App\Http\Controllers;

use App\Models\FeedbackQuestion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminFeedbackQuestionController extends Controller
{
    public function index(): View
    {
        return view('admin.feedback.index', [
            'questions' => FeedbackQuestion::orderBy('category')->orderBy('id')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.feedback.create');
    }

    public function store(Request $request): RedirectResponse
    {
        FeedbackQuestion::create($this->validated($request));

        return redirect()->route('admin.feedback.index')->with('status', 'Feedback question created.');
    }

    public function edit(FeedbackQuestion $feedbackQuestion): View
    {
        return view('admin.feedback.edit', compact('feedbackQuestion'));
    }

    public function update(Request $request, FeedbackQuestion $feedbackQuestion): RedirectResponse
    {
        $feedbackQuestion->update($this->validated($request));

        return redirect()->route('admin.feedback.index')->with('status', 'Feedback question updated.');
    }

    public function destroy(FeedbackQuestion $feedbackQuestion): RedirectResponse
    {
        $feedbackQuestion->delete();

        return redirect()->route('admin.feedback.index')->with('status', 'Feedback question deleted.');
    }

    public function toggle(FeedbackQuestion $feedbackQuestion): RedirectResponse
    {
        $feedbackQuestion->update(['is_active' => ! $feedbackQuestion->is_active]);

        return back()->with('status', 'Feedback question status updated.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'question_text' => ['required', 'string'],
            'category' => ['required', Rule::in(['usability', 'learning effectiveness', 'engagement', 'assessment'])],
            'is_active' => ['nullable', 'boolean'],
        ]) + ['is_active' => false];
    }
}
