@extends('layouts.app')

@section('title', 'Leaderboard | ShellFix')
@section('page-title', 'Leaderboard')
@section('page-description', 'See top learners and compare progress across ShellFix challenges.')

@section('content')
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th scope="col">Rank</th>
                            <th scope="col">Student</th>
                            <th scope="col">Set</th>
                            <th scope="col">Answered</th>
                            <th scope="col">Total Score</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($scores as $score)
                            <tr>
                                <td class="fw-semibold">{{ $loop->iteration }}</td>
                                <td>
                                    {{ $score->user?->name ?? 'Student ' . substr($score->exam_session_id, 0, 8) }}
                                    @if ($score->exam_session_id === $examSessionId)
                                        <span class="badge text-bg-info ms-2">You</span>
                                    @endif
                                </td>
                                <td>Set {{ $score->set_no }}</td>
                                <td>{{ $score->answered_count }}</td>
                                <td class="fw-semibold">{{ $score->total_score }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-secondary py-4">No scores have been submitted yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
