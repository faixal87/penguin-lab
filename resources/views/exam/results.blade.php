@extends('layouts.app')

@section('title', 'Results | ShellFix')
@section('page-title', 'Exam Results')
@section('page-description', 'Review your answered questions and total score for Set ' . $examSet . '.')

@section('content')
    <div class="row g-4 mb-4">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm stat-card">
                <div class="card-body">
                    <h2 class="h6 text-secondary">Assigned Set</h2>
                    <p class="display-6 fw-semibold mb-0">Set {{ $examSet }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-0 shadow-sm stat-card">
                <div class="card-body">
                    <h2 class="h6 text-secondary">Total Score</h2>
                    <p class="display-6 fw-semibold mb-1">{{ $totalScore }}</p>
                    @if (auth()->user()->isStudent())
                        <span class="badge text-bg-primary badge-glow">{{ $badge }}</span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th scope="col">Question</th>
                            <th scope="col">Answer</th>
                            <th scope="col">Result</th>
                            <th scope="col">Hint</th>
                            <th scope="col">Score</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($answers as $answer)
                            <tr>
                                <td class="fw-semibold">{{ $answer->scenario->title }}</td>
                                <td><code>{{ $answer->answer }}</code></td>
                                <td>
                                    <span class="badge {{ $answer->is_correct ? 'text-bg-success' : 'text-bg-danger' }}">
                                        {{ $answer->is_correct ? 'Correct' : 'Wrong' }}
                                    </span>
                                </td>
                                <td>{{ $answer->hint_used ? 'Yes' : 'No' }}</td>
                                <td>{{ $answer->score_awarded }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-secondary py-4">No answers submitted yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
