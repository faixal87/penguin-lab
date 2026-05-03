@extends('layouts.app')

@section('title', 'Dashboard | ShellFix')
@section('page-title', 'Dashboard')
@section('page-description', 'Track your ShellFix learning progress and recent activity.')

@section('content')
    @if (session('status'))
        <div class="alert alert-success border-0 shadow-sm">{{ session('status') }}</div>
    @endif

    <div class="row g-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm stat-card">
                <div class="card-body">
                    <h2 class="h6 text-secondary">Completed Scenarios</h2>
                    <p class="display-6 fw-semibold mb-0">{{ $answeredCount ?? 0 }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm stat-card">
                <div class="card-body">
                    <h2 class="h6 text-secondary">Total Score</h2>
                    <p class="display-6 fw-semibold mb-0">{{ $totalScore ?? 0 }}</p>
                </div>
            </div>
        </div>
        @if (auth()->user()->isStudent())
            <div class="col-md-4">
                <div class="card border-0 shadow-sm stat-card">
                    <div class="card-body">
                        <h2 class="h6 text-secondary">Achievement Badge</h2>
                        <span class="badge text-bg-primary fs-6 badge-glow">{{ $badge }}</span>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <div class="card border-0 shadow-sm mt-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center gap-3 flex-wrap">
                <div>
                    <h2 class="h5">Welcome to ShellFix</h2>
                    <p class="mb-0 text-secondary">Use the sidebar to explore scenarios, submit answers, and review your results.</p>
                </div>
                @if (auth()->user()->isStudent() && $terminalRunning)
                    <a href="{{ route('terminal.index') }}" class="btn btn-primary">Launch Terminal</a>
                @endif
            </div>
        </div>
    </div>

    @if (auth()->user()->isStudent())
        @if ($shouldShowFeedbackPrompt)
            <div class="alert alert-warning border-0 shadow-sm mt-4">
                <div class="d-flex justify-content-between align-items-center gap-3">
                    <div>
                        <div class="fw-semibold">Course feedback is ready</div>
                        <div>Please complete the feedback form to help improve ShellFix.</div>
                    </div>
                    <a href="{{ route('feedback.form') }}" class="btn btn-warning">Answer Feedback</a>
                </div>
            </div>
        @elseif ($hasSubmittedFeedback)
            <div class="alert alert-success border-0 shadow-sm mt-4">
                Thank you. You have submitted your course feedback.
            </div>
        @endif

        <div class="row g-4 mt-1">
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm h-100 xp-card">
                    <div class="card-body">
                        <h2 class="h5">Class Ranking</h2>
                        @if ($latestClass)
                            <p class="mb-1 text-secondary">{{ $latestClass->class_name }} ({{ $latestClass->course_code }})</p>
                            <p class="display-6 fw-semibold mb-0">
                                Your Rank: {{ $classRank ? '#' . $classRank . ' of ' . $classSize : 'Not ranked' }}
                            </p>
                        @else
                            <p class="text-secondary mb-0">You are not enrolled in a class yet.</p>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card border-0 shadow-sm h-100 xp-card">
                    <div class="card-body">
                        <h2 class="h5">Module Completion</h2>
                        <p class="mb-2">Completed: {{ $completedModules }} / {{ $totalModules }} modules</p>
                        <div class="progress xp-progress" role="progressbar" aria-label="Module completion" aria-valuenow="{{ $completionPercentage }}" aria-valuemin="0" aria-valuemax="100">
                            <div class="progress-bar" style="width: {{ $completionPercentage }}%">{{ $completionPercentage }}%</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mt-4">
            <div class="card-body">
                <h2 class="h5">Top 5 Scorers</h2>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Rank</th>
                                <th>Student</th>
                                <th>Total Score</th>
                                <th>Badge</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($topScorers as $student)
                                @php($studentScore = $student->total_score ?? 0)
                                <tr>
                                    <td class="fw-semibold">{{ $loop->iteration }}</td>
                                    <td>{{ $student->name }}</td>
                                    <td>{{ $studentScore }}</td>
                                    <td>
                                        @if ($student->isStudent())
                                            <span class="badge text-bg-primary badge-glow">{{ $badgeService->forScore($studentScore) }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-secondary py-4">No classmates have scores yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
@endsection
