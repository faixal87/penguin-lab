<?php

namespace App\Http\Controllers;

use App\Models\FeedbackAnswer;
use App\Models\FeedbackQuestion;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\View\View;

class FeedbackReportController extends Controller
{
    public function dashboard(Request $request): View
    {
        $answers = $this->filteredAnswers($request)->get();
        $questionAverages = $answers
            ->groupBy('question_id')
            ->map(function ($items) {
                $question = $items->first()->question;

                return (object) [
                    'question_text' => $question->question_text,
                    'category' => $question->category,
                    'average_rating' => $items->avg('rating'),
                    'response_count' => $items->count(),
                ];
            })
            ->sortBy([['category', 'asc'], ['question_text', 'asc']])
            ->values();

        $categoryAverages = $answers
            ->groupBy(fn ($answer) => $answer->question->category)
            ->map(fn ($items, $category) => (object) [
                'category' => $category,
                'average_rating' => $items->avg('rating'),
                'response_count' => $items->count(),
            ])
            ->sortBy('category')
            ->values();

        return view('feedback.summary', [
            'filters' => $this->filterOptions($request),
            'selected' => $request->only(['class_id', 'lecturer_id', 'semester', 'category']),
            'totalRespondents' => $answers->pluck('user_id')->unique()->count(),
            'overallAverage' => $answers->avg('rating'),
            'categoryAverages' => $categoryAverages,
            'questionAverages' => $questionAverages,
            'topQuestions' => $questionAverages->where('response_count', '>', 0)->sortByDesc('average_rating')->take(5),
            'lowQuestions' => $questionAverages->where('response_count', '>', 0)->sortBy('average_rating')->take(5),
        ]);
    }

    public function raw(Request $request): View
    {
        return view('feedback.raw', [
            'filters' => $this->filterOptions($request),
            'selected' => $request->only(['class_id', 'lecturer_id', 'semester', 'category']),
            'answers' => $this->filteredAnswers($request)->latest('created_at')->get(),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $answers = $this->filteredAnswers($request)->latest('created_at')->get();

        return response()->streamDownload(function () use ($answers, $request) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['student name', 'matric_no', 'class_name', 'question', 'category', 'rating', 'submitted date']);

            foreach ($answers as $answer) {
                fputcsv($handle, [
                    $answer->user->name,
                    $answer->user->matric_no ?? $answer->user->registration_no,
                    $this->classNamesFor($answer, $request),
                    $answer->question->question_text,
                    $answer->question->category,
                    $answer->rating,
                    $answer->created_at?->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($handle);
        }, 'shellfix-feedback.csv', ['Content-Type' => 'text/csv']);
    }

    private function filteredAnswers(Request $request): Builder
    {
        $user = $request->user();

        return FeedbackAnswer::query()
            ->with(['question', 'user.enrolledClasses'])
            ->whereHas('user', function (Builder $query) use ($request, $user) {
                $query->where('role', 'student');

                if ($request->filled('semester')) {
                    $query->where('semester', $request->query('semester'));
                }

                if ($user->isLecturer()) {
                    $query->whereHas('enrolledClasses', fn (Builder $classQuery) => $classQuery->where('lecturer_id', $user->id));
                }

                if ($request->filled('class_id')) {
                    $query->whereHas('enrolledClasses', fn (Builder $classQuery) => $classQuery->where('classes.id', $request->query('class_id')));
                }

                if ($user->isAdmin() && $request->filled('lecturer_id')) {
                    $query->whereHas('enrolledClasses', fn (Builder $classQuery) => $classQuery->where('lecturer_id', $request->query('lecturer_id')));
                }
            })
            ->whereHas('question', function (Builder $query) use ($request) {
                if ($request->filled('category')) {
                    $query->where('category', $request->query('category'));
                }
            });
    }

    private function filterOptions(Request $request): array
    {
        $user = $request->user();

        return [
            'classes' => SchoolClass::query()
                ->when($user->isLecturer(), fn ($query) => $query->where('lecturer_id', $user->id))
                ->orderBy('class_name')
                ->get(),
            'lecturers' => $user->isAdmin() ? User::where('role', 'lecturer')->orderBy('name')->get() : collect(),
            'semesters' => User::where('role', 'student')->whereNotNull('semester')->distinct()->orderBy('semester')->pluck('semester'),
            'categories' => FeedbackQuestion::distinct()->orderBy('category')->pluck('category'),
        ];
    }

    private function classNamesFor(FeedbackAnswer $answer, Request $request): string
    {
        $classes = $answer->user->enrolledClasses;

        if ($request->filled('class_id')) {
            $classes = $classes->where('id', (int) $request->query('class_id'));
        }

        if ($request->user()->isLecturer()) {
            $classes = $classes->where('lecturer_id', $request->user()->id);
        }

        if ($request->user()->isAdmin() && $request->filled('lecturer_id')) {
            $classes = $classes->where('lecturer_id', (int) $request->query('lecturer_id'));
        }

        return $classes->pluck('class_name')->join(', ');
    }
}
