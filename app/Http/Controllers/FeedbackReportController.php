<?php

namespace App\Http\Controllers;

use App\Models\AiFeedbackSummary;
use App\Models\FeedbackAnswer;
use App\Models\FeedbackQuestion;
use App\Models\SchoolClass;
use App\Models\User;
use App\Services\OpenAIService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\View\View;

class FeedbackReportController extends Controller
{
    public function __construct(private OpenAIService $openAI)
    {
    }

    public function dashboard(Request $request): View
    {
        return view('feedback.summary', $this->summaryPayload($request));
    }

    public function generateAiSummary(Request $request): RedirectResponse
    {
        $this->authorizeSummaryScope($request);

        $payload = $this->summaryPayload($request);
        $fallbackSummary = $this->ruleSummaryText($payload['aiSummary']);
        $result = $this->openAI->generateFeedbackSummary($payload['promptData'], $fallbackSummary);

        AiFeedbackSummary::create([
            'generated_by' => $request->user()->id,
            'scope_type' => $request->filled('class_id') ? 'class' : 'all',
            'class_id' => $request->filled('class_id') ? (int) $request->input('class_id') : null,
            'prompt_data' => $payload['promptData'],
            'summary' => $result['summary'],
        ]);

        $message = $result['source'] === 'openai'
            ? 'AI summary generated using OpenAI.'
            : ($result['error'] ?: 'Rule-based summary generated.');

        return redirect()
            ->route('feedback.summary', $request->only(['class_id', 'lecturer_id', 'semester', 'category']))
            ->with($result['source'] === 'openai' ? 'status' : 'warning', $message);
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

    private function summaryPayload(Request $request): array
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
        $overallAverage = $answers->avg('rating');
        $aiSummary = $this->aiSummary(
            $answers,
            $categoryAverages,
            $questionAverages,
            $classStats,
            $overallAverage
        );
        $topQuestions = $questionAverages->where('response_count', '>', 0)->sortByDesc('average_rating')->take(5);
        $lowQuestions = $questionAverages->where('response_count', '>', 0)->sortBy('average_rating')->take(5);

        return [
            'filters' => $this->filterOptions($request),
            'selected' => $request->only(['class_id', 'lecturer_id', 'semester', 'category']),
            'totalRespondents' => $answers->pluck('user_id')->unique()->count(),
            'overallAverage' => $overallAverage,
            'categoryAverages' => $categoryAverages,
            'questionAverages' => $questionAverages,
            'topQuestions' => $topQuestions,
            'lowQuestions' => $lowQuestions,
            'classStats' => $classStats,
            'ratingDistribution' => $ratingDistribution,
            'aiSummary' => $aiSummary,
            'latestAiSummary' => $this->latestAiSummary($request),
            'promptData' => $this->promptData($request, $answers, $categoryAverages, $topQuestions, $lowQuestions, $classStats, $overallAverage),
        ];
    }

    private function authorizeSummaryScope(Request $request): void
    {
        $request->validate([
            'class_id' => ['nullable', 'integer', 'exists:classes,id'],
            'lecturer_id' => ['nullable', 'integer', 'exists:users,id'],
            'semester' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:255'],
        ]);

        if ($request->filled('class_id') && $request->user()->isLecturer()) {
            $class = SchoolClass::findOrFail($request->input('class_id'));
            abort_unless((int) $class->lecturer_id === (int) $request->user()->id, 403);
        }
    }

    private function latestAiSummary(Request $request): ?AiFeedbackSummary
    {
        return AiFeedbackSummary::query()
            ->with(['generator', 'schoolClass'])
            ->where('generated_by', $request->user()->id)
            ->where('scope_type', $request->filled('class_id') ? 'class' : 'all')
            ->when(
                $request->filled('class_id'),
                fn ($query) => $query->where('class_id', $request->input('class_id')),
                fn ($query) => $query->whereNull('class_id')
            )
            ->latest('created_at')
            ->first();
    }

    private function promptData(Request $request, $answers, $categoryAverages, $topQuestions, $lowQuestions, $classStats, ?float $overallAverage): array
    {
        return [
            'scope' => [
                'type' => $request->filled('class_id') ? 'class' : 'all',
                'class_id' => $request->filled('class_id') ? (int) $request->input('class_id') : null,
                'semester_filter' => $request->input('semester'),
                'category_filter' => $request->input('category'),
            ],
            'overall_average' => $overallAverage ? round((float) $overallAverage, 2) : null,
            'response_count' => [
                'respondents' => $answers->pluck('user_id')->unique()->count(),
                'ratings' => $answers->count(),
            ],
            'average_by_category' => $categoryAverages->map(fn ($item) => [
                'category' => $item->category,
                'average_rating' => $item->average_rating ? round((float) $item->average_rating, 2) : null,
                'response_count' => $item->response_count,
            ])->values()->all(),
            'top_5_highest_questions' => $topQuestions->map(fn ($item) => [
                'question' => $item->question_text,
                'category' => $item->category,
                'average_rating' => round((float) $item->average_rating, 2),
                'response_count' => $item->response_count,
            ])->values()->all(),
            'top_5_lowest_questions' => $lowQuestions->map(fn ($item) => [
                'question' => $item->question_text,
                'category' => $item->category,
                'average_rating' => round((float) $item->average_rating, 2),
                'response_count' => $item->response_count,
            ])->values()->all(),
            'class_comparison' => $classStats->map(fn ($item) => [
                'class_name' => $item->class_name,
                'average_rating' => $item->average_rating ? round((float) $item->average_rating, 2) : null,
                'respondents' => $item->response_count,
                'rating_count' => $item->rating_count,
            ])->values()->all(),
        ];
    }

    private function ruleSummaryText(array $summary): string
    {
        return implode("\n\n", [
            "## Overall interpretation\n{$summary['learningImpact']}",
            "## Strengths\n{$summary['strengths']}",
            "## Weaknesses\n{$summary['weaknesses']}",
            "## Suggested CQI actions\n{$summary['cqi']}",
            "## Research paper paragraph draft\n{$summary['paperParagraph']}",
            "## Possible discussion points\n- {$summary['engagement']}\n- {$summary['classComparison']}\n- Compare category-level averages with the lowest-rated question items to prioritize improvements.",
        ]);
    }

    private function filteredAnswers(Request $request): Builder
    {
        $user = $request->user();

        return FeedbackAnswer::query()
            ->with(['question', 'user.enrolledClasses'])
            ->whereHas('user', function (Builder $query) use ($request, $user) {
                $query->where('role', 'student');

                if ($request->filled('semester')) {
                    $query->where('semester', $request->input('semester'));
                }

                if ($user->isLecturer()) {
                    $query->whereHas('enrolledClasses', fn (Builder $classQuery) => $classQuery->where('lecturer_id', $user->id));
                }

                if ($request->filled('class_id')) {
                    $query->whereHas('enrolledClasses', fn (Builder $classQuery) => $classQuery->where('classes.id', $request->input('class_id')));
                }

                if ($user->isAdmin() && $request->filled('lecturer_id')) {
                    $query->whereHas('enrolledClasses', fn (Builder $classQuery) => $classQuery->where('lecturer_id', $request->input('lecturer_id')));
                }
            })
            ->whereHas('question', function (Builder $query) use ($request) {
                if ($request->filled('category')) {
                    $query->where('category', $request->input('category'));
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
            $classes = $classes->where('id', (int) $request->input('class_id'));
        }

        if ($request->user()->isLecturer()) {
            $classes = $classes->where('lecturer_id', $request->user()->id);
        }

        if ($request->user()->isAdmin() && $request->filled('lecturer_id')) {
            $classes = $classes->where('lecturer_id', (int) $request->input('lecturer_id'));
        }

        return $classes->pluck('class_name')->join(', ');
    }

    private function classStats($answers, Request $request)
    {
        return $answers
            ->flatMap(function (FeedbackAnswer $answer) use ($request) {
                $classes = $answer->user->enrolledClasses;

                if ($request->filled('class_id')) {
                    $classes = $classes->where('id', (int) $request->input('class_id'));
                }

                if ($request->user()->isLecturer()) {
                    $classes = $classes->where('lecturer_id', $request->user()->id);
                }

                if ($request->user()->isAdmin() && $request->filled('lecturer_id')) {
                    $classes = $classes->where('lecturer_id', (int) $request->input('lecturer_id'));
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
