<?php

namespace App\Http\Controllers;

use App\Models\Semester;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SemesterController extends Controller
{
    public function index(): View
    {
        return view('admin.semesters.index', [
            'semesters' => Semester::latest()->get(),
            'currentSemester' => Semester::current(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'is_current' => ['nullable', 'boolean'],
        ]);

        DB::transaction(function () use ($validated, $request) {
            if ($request->boolean('is_current')) {
                Semester::query()->update(['is_current' => false]);
            }

            Semester::create($validated + ['is_current' => $request->boolean('is_current')]);
        });

        return back()->with('status', 'Semester created.');
    }

    public function setCurrent(Semester $semester): RedirectResponse
    {
        DB::transaction(function () use ($semester) {
            Semester::query()->update(['is_current' => false]);
            $semester->update(['is_current' => true]);
        });

        return back()->with('status', 'Current semester updated.');
    }
}
