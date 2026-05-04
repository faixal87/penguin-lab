@extends('layouts.app')

@section('title', 'Lecturer Dashboard | ShellFix')
@section('page-title', 'Lecturer Dashboard')
@section('page-description', 'Create classes and enroll students by CSV.')

@section('content')
    @if (session('status'))
        <div class="alert alert-success border-0 shadow-sm">{{ session('status') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger border-0 shadow-sm">{{ session('error') }}</div>
    @endif

    <div class="alert alert-info border-0 shadow-sm">
        Current semester: <strong>{{ $currentSemester?->name ?? 'Not set' }}</strong>
        @unless($currentSemester)
            <span class="ms-2">Admin must set current semester before classes can be created.</span>
        @endunless
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <h2 class="h5">Create Class</h2>
            <form method="POST" action="{{ route('lecturer.classes.store') }}" class="row g-3">
                @csrf
                <div class="col-md-5">
                    <label for="class_name" class="form-label">Class Name</label>
                    <input type="text" class="form-control @error('class_name') is-invalid @enderror" id="class_name" name="class_name" value="{{ old('class_name') }}" required>
                    @error('class_name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-4">
                    <label for="course_code" class="form-label">Course Code</label>
                    <input type="text" class="form-control @error('course_code') is-invalid @enderror" id="course_code" name="course_code" value="{{ old('course_code') }}" required>
                    @error('course_code')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100" @disabled(! $currentSemester)>Create</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="h5 mb-0">Overall Leaderboard - {{ $currentSemester?->name ?? 'No current semester' }}</h2>
                <a href="{{ route('scoreboard') }}" class="btn btn-sm btn-outline-primary">Open Scoreboard</a>
            </div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead><tr><th>Rank</th><th>Student</th><th>Class</th><th>Total Score</th><th>Badge</th></tr></thead>
                    <tbody>
                    @forelse($leaderboard as $student)
                        @php($score = $student->total_score ?? 0)
                        <tr>
                            <td class="fw-semibold">{{ $loop->iteration }}</td>
                            <td><img src="{{ $student->profilePhotoUrl() }}" class="rank-avatar me-2" alt="{{ $student->name }}"><span class="avatar-preview"><img src="{{ $student->profilePhotoUrl() }}" alt="{{ $student->name }}"></span>{{ $student->name }}</td>
                            <td>{{ $student->enrolledClasses->pluck('class_name')->join(', ') ?: '-' }}</td>
                            <td class="fw-semibold">{{ $score }}</td>
                            <td><span class="badge text-bg-primary badge-glow">{{ $badgeService->forScore($score) }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-secondary py-4">No student scores yet.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <h2 class="h5">My Classes</h2>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Class</th>
                            <th>Course</th>
                            <th>Semester</th>
                            <th>Students</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($classes as $class)
                            <tr>
                                <td class="fw-semibold">{{ $class->class_name }}</td>
                                <td>{{ $class->course_code }}</td>
                                <td>{{ $class->semester?->name ?? '-' }}</td>
                                <td>{{ $class->students_count }}</td>
                                <td class="text-end">
                                    <a href="{{ route('lecturer.classes.show', $class) }}" class="btn btn-sm btn-outline-primary">Manage</a>
                                    <a href="{{ route('lecturer.classes.edit', $class) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                                    <form method="POST" action="{{ route('lecturer.classes.destroy', $class) }}" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this class?')">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-secondary py-4">No classes created yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
