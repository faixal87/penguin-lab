<?php

namespace App\Http\Controllers;

use App\Models\QuestionSet;
use Illuminate\Http\Request;
use Illuminate\View\View;

class QuestionSetController extends Controller
{
    public function index(Request $request): View
    {
        $sets = QuestionSet::with('setQuestions.question')
            ->orderBy('name')
            ->get();
        $selectedSet = (int) $request->query('set_id', $sets->first()?->id ?? 0);
        $search = trim((string) $request->query('search', ''));
        $category = $request->query('category');

        $selectedQuestionSet = $sets->firstWhere('id', $selectedSet);

        $categories = $selectedQuestionSet
            ? $selectedQuestionSet->setQuestions->pluck('question.category')->filter()->unique()->sort()->values()
            : collect();

        $questions = $selectedQuestionSet
            ? $selectedQuestionSet->setQuestions()
            ->with('question')
            ->when($search !== '', function ($query) use ($search) {
                $query->whereHas('question', function ($inner) use ($search) {
                    $inner->where('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhere('category', 'like', "%{$search}%")
                        ->orWhere('difficulty', 'like', "%{$search}%");
                });
            })
            ->when($category, fn ($query) => $query->whereHas('question', fn ($inner) => $inner->where('category', $category)))
            ->get()
            : collect();

        return view('question-sets.index', compact(
            'sets',
            'selectedSet',
            'selectedQuestionSet',
            'categories',
            'category',
            'search',
            'questions'
        ));
    }
}
