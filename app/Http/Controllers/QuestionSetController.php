<?php

namespace App\Http\Controllers;

use App\Models\Scenario;
use Illuminate\Http\Request;
use Illuminate\View\View;

class QuestionSetController extends Controller
{
    public function index(Request $request): View
    {
        $sets = Scenario::whereNotNull('set_no')
            ->distinct()
            ->orderBy('set_no')
            ->pluck('set_no');
        $selectedSet = (int) $request->query('set_no', $sets->first() ?? 1);
        $search = trim((string) $request->query('search', ''));
        $category = $request->query('category');

        $categories = Scenario::whereNotNull('question_type')
            ->distinct()
            ->orderBy('question_type')
            ->pluck('question_type');

        $questions = Scenario::query()
            ->where('set_no', $selectedSet)
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhere('question_type', 'like', "%{$search}%")
                        ->orWhere('difficulty', 'like', "%{$search}%");
                });
            })
            ->when($category, fn ($query) => $query->where('question_type', $category))
            ->orderBy('id')
            ->get();

        return view('question-sets.index', compact(
            'sets',
            'selectedSet',
            'categories',
            'category',
            'search',
            'questions'
        ));
    }
}
