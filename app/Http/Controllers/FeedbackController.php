<?php

namespace App\Http\Controllers;

use App\Models\FeedbackAnswer;
use App\Models\FeedbackQuestion;
use App\Services\CourseFeedbackService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FeedbackController extends Controller
{
    public function __construct(private CourseFeedbackService $feedbackControls)
    {
    }

    public function form(Request $request): View|RedirectResponse
    {
        if (! $this->feedbackControls->isEnabledFor($request->user())) {
            return redirect()->route('dashboard')->with('status', 'Course feedback is not currently enabled for your account.');
        }

        if ($this->hasSubmitted($request)) {
            return redirect()->route('dashboard')->with('status', 'You have already submitted course feedback.');
        }

        if (! FeedbackQuestion::where('is_active', true)->exists()) {
            return redirect()->route('dashboard')->with('status', 'No active feedback questions are available.');
        }

        return view('feedback.form', [
            'questions' => FeedbackQuestion::where('is_active', true)
                ->orderBy('category')
                ->orderBy('id')
                ->get()
                ->groupBy('category'),
        ]);
    }

    public function submit(Request $request): RedirectResponse
    {
        if (! $this->feedbackControls->isEnabledFor($request->user())) {
            return redirect()->route('dashboard')->with('status', 'Course feedback is not currently enabled for your account.');
        }

        if ($this->hasSubmitted($request)) {
            return redirect()->route('dashboard')->with('status', 'You have already submitted course feedback.');
        }

        $activeQuestionIds = FeedbackQuestion::where('is_active', true)->pluck('id');

        if ($activeQuestionIds->isEmpty()) {
            return redirect()->route('dashboard')->with('status', 'No active feedback questions are available.');
        }

        $rules = [];

        foreach ($activeQuestionIds as $questionId) {
            $rules["ratings.{$questionId}"] = ['required', 'integer', 'between:1,5'];
        }

        $validated = $request->validate($rules);

        foreach ($validated['ratings'] as $questionId => $rating) {
            FeedbackAnswer::create([
                'user_id' => $request->user()->id,
                'question_id' => $questionId,
                'rating' => $rating,
            ]);
        }

        return redirect()->route('dashboard')->with('status', 'Thank you. Your feedback has been submitted.');
    }

    public function summary(Request $request): View
    {
        $studentIds = null;

        if ($request->user()->isLecturer()) {
            $studentIds = $request->user()
                ->teachingClasses()
                ->with('students:id')
                ->get()
                ->flatMap(fn ($class) => $class->students->pluck('id'))
                ->unique()
                ->values();
        }

        $questionAverages = FeedbackQuestion::query()
            ->leftJoin('feedback_answers', 'feedback_questions.id', '=', 'feedback_answers.question_id')
            ->when($studentIds !== null, fn ($query) => $query->whereIn('feedback_answers.user_id', $studentIds))
            ->select('feedback_questions.id', 'feedback_questions.question_text', 'feedback_questions.category')
            ->selectRaw('AVG(feedback_answers.rating) as average_rating')
            ->selectRaw('COUNT(feedback_answers.id) as response_count')
            ->groupBy('feedback_questions.id', 'feedback_questions.question_text', 'feedback_questions.category')
            ->orderBy('feedback_questions.category')
            ->orderBy('feedback_questions.id')
            ->get();

        $categoryAverages = FeedbackQuestion::query()
            ->leftJoin('feedback_answers', 'feedback_questions.id', '=', 'feedback_answers.question_id')
            ->when($studentIds !== null, fn ($query) => $query->whereIn('feedback_answers.user_id', $studentIds))
            ->select('feedback_questions.category')
            ->selectRaw('AVG(feedback_answers.rating) as average_rating')
            ->selectRaw('COUNT(feedback_answers.id) as response_count')
            ->groupBy('feedback_questions.category')
            ->orderBy('feedback_questions.category')
            ->get();

        return view('feedback.summary', compact('questionAverages', 'categoryAverages'));
    }

    private function hasSubmitted(Request $request): bool
    {
        return FeedbackAnswer::where('user_id', $request->user()->id)->exists();
    }
}
