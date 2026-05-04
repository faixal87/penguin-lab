@extends('layouts.app')

@section('title', 'Leaderboard | ShellFix')
@section('page-title', 'Leaderboard')
@section('page-description', 'Top learners for ' . ($currentSemester?->name ?? 'all semesters') . '.')

@section('content')
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th scope="col">Rank</th>
                            <th scope="col">Student</th>
                            <th scope="col">Class</th>
                            <th scope="col">Semester</th>
                            <th scope="col">Total Score</th>
                            <th scope="col">Badge</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($scores as $student)
                            @php($score = $student->total_score ?? 0)
                            <tr>
                                <td class="fw-semibold">{{ $loop->iteration }}</td>
                                <td>
                                    <span class="position-relative d-inline-block">
                                        <img src="{{ $student->profilePhotoUrl() }}" class="rank-avatar me-2" alt="{{ $student->name }}">
                                        <span class="avatar-preview"><img src="{{ $student->profilePhotoUrl() }}" alt="{{ $student->name }}"></span>
                                    </span>
                                    {{ $student->name }}
                                    @if ($student->id === auth()->id())
                                        <span class="badge text-bg-info ms-2">You</span>
                                    @endif
                                </td>
                                <td>{{ $student->enrolledClasses->pluck('class_name')->join(', ') ?: '-' }}</td>
                                <td>{{ $student->enrolledClasses->pluck('semester.name')->filter()->unique()->join(', ') ?: '-' }}</td>
                                <td class="fw-semibold">{{ $score }}</td>
                                <td><span class="badge text-bg-primary badge-glow">{{ $badgeService->forScore($score) }}</span></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-secondary py-4">No scores have been submitted yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
