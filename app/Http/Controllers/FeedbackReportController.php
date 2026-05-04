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

        $classStats = $this->classStats($answers, $request);
        $ratingDistribution = collect(range(1, 5))->map(fn ($rating) => (object) [
            'rating' => $rating,
            'count' => $answers->where('rating', $rating)->count(),
        ]);
        $aiSummary = $this->aiSummary(
            $answers,
            $categoryAverages,
            $questionAverages,
            $classStats,
            $answers->avg('rating')
        );

        return view('feedback.summary', [
            'filters' => $this->filterOptions($request),
            'selected' => $request->only(['class_id', 'lecturer_id', 'semester', 'category']),
            'totalRespondents' => $answers->pluck('user_id')->unique()->count(),
            'overallAverage' => $answers->avg('rating'),
            'categoryAverages' => $categoryAverages,
            'questionAverages' => $questionAverages,
            'topQuestions' => $questionAverages->where('response_count', '>', 0)->sortByDesc('average_rating')->take(5),
            'lowQuestions' => $questionAverages->where('response_count', '>', 0)->sortBy('average_rating')->take(5),
            'classStats' => $classStats,
            'ratingDistribution' => $ratingDistribution,
            'aiSummary' => $aiSummary,
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

    private function classStats($answers, Request $request)
    {
        return $answers
            ->flatMap(function (FeedbackAnswer $answer) use ($request) {
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

                return $classes->map(fn ($class) => [
                    'class_id' => $class->id,
                    'class_name' => $class->class_name,
                    'student_id' => $answer->user_id,
                    'rating' => $answer->rating,
                ]);
            })
            ->groupBy('class_id')
            ->map(fn ($items) => (object) [
                'class_name' => $items->first()['class_name'],
                'average_rating' => $items->avg('rating'),
                'response_count' => $items->pluck('student_id')->unique()->count(),
                'rating_count' => $items->count(),
            ])
            ->sortByDesc('average_rating')
            ->values();
    }

    private function aiSummary($answers, $categoryAverages, $questionAverages, $classStats, ?float $overallAverage): array
    {
        $highestCategory = $categoryAverages->where('response_count', '>', 0)->sortByDesc('average_rating')->first();
        $lowestCategory = $categoryAverages->where('response_count', '>', 0)->sortBy('average_rating')->first();
        $lowestQuestions = $questionAverages->where('response_count', '>', 0)->sortBy('average_rating')->take(5)->pluck('question_text');
        $bestClass = $classStats->first();
        $weakClass = $classStats->sortBy('average_rating')->first();
        $averageText = $overallAverage ? number_format($overallAverage, 2) : '0.00';
        $strengthLevel = ($overallAverage ?? 0) >= 4 ? 'strong' : (($overallAverage ?? 0) >= 3 ? 'moderate' : 'developing');
        $highestCategoryName = $highestCategory?->category ?? 'the strongest category';
        $lowestCategoryName = $lowestCategory?->category ?? 'the lowest-rated category';

        return [
            'strengths' => $highestCategory
                ? "Students rated {$highestCategory->category} highest with an average of " . number_format($highestCategory->average_rating, 2) . "/5, suggesting this area is a current strength of ShellFix."
                : 'No category strength can be identified until students submit feedback.',
            'weaknesses' => $lowestCategory
                ? "The lowest category is {$lowestCategory->category} with an average of " . number_format($lowestCategory->average_rating, 2) . "/5. This should be treated as the first improvement area."
                : 'No weakness pattern can be identified yet.',
            'learningImpact' => "The overall course feedback average is {$averageText}/5, indicating a {$strengthLevel} perceived learning impact across the current respondent group.",
            'engagement' => $answers->pluck('user_id')->unique()->count() > 0
                ? 'Student engagement can be evidenced through submitted feedback responses and category-level ratings across the learning experience.'
                : 'Student engagement cannot be evaluated yet because no feedback responses are available.',
            'cqi' => $lowestQuestions->isNotEmpty()
                ? 'Suggested CQI actions: review the lowest-rated items, refine scenario instructions, improve feedback clarity, and add targeted lecturer support for weaker categories.'
                : 'Suggested CQI actions will become more precise after feedback is collected.',
            'classComparison' => ($bestClass && $weakClass)
                ? "Class comparison shows {$bestClass->class_name} currently has the strongest average rating (" . number_format($bestClass->average_rating, 2) . "/5), while {$weakClass->class_name} needs closer review (" . number_format($weakClass->average_rating, 2) . "/5)."
                : 'Class comparison is not available yet.',
            'paperParagraph' => "Based on the collected ShellFix course feedback, students reported an overall average rating of {$averageText}/5. The findings suggest that {$highestCategoryName} contributed positively to the learning experience, while {$lowestCategoryName} should be prioritized for continuous quality improvement. These results indicate that ShellFix can support Linux command learning, while further refinements should focus on the lowest-rated questions and class-level differences to improve usability, engagement, and assessment effectiveness.",
        ];
    }
}
