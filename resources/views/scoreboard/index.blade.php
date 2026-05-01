@extends('layouts.app')

@section('title', 'Class Scoreboard | ShellFix')
@section('page-title', 'Class Scoreboard')
@section('page-description', 'Compare enrolled student scores by class.')

@section('content')
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('scoreboard') }}" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label for="sort" class="form-label">Sort by</label>
                    <select class="form-select" id="sort" name="sort">
                        <option value="highest" @selected($sort === 'highest')>Highest score</option>
                        <option value="lowest" @selected($sort === 'lowest')>Lowest score</option>
                        <option value="name" @selected($sort === 'name')>Student name</option>
                        <option value="matric_no" @selected($sort === 'matric_no')>Matric no</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Apply</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th scope="col">Rank</th>
                            <th scope="col">Student</th>
                            <th scope="col">Matric / Registration</th>
                            <th scope="col">Class</th>
                            <th scope="col">Total Score</th>
                            <th scope="col">Badge</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($students as $student)
                            @php
                                $totalScore = $student->total_score ?? 0;
                            @endphp
                            <tr>
                                <td class="fw-semibold">{{ $loop->iteration }}</td>
                                <td>{{ $student->name }}</td>
                                <td>{{ $student->matric_no ?? $student->registration_no ?? '-' }}</td>
                                <td>{{ $student->enrolledClasses->pluck('class_name')->join(', ') ?: ($student->class_name ?? '-') }}</td>
                                <td class="fw-semibold">{{ $totalScore }}</td>
                                <td>
                                    @if ($student->isStudent())
                                        <span class="badge text-bg-primary badge-glow">{{ $badgeService->forScore($totalScore) }}</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-secondary py-4">No enrolled student scores are available yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
