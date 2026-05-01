@extends('layouts.app')

@section('title', 'Raw Feedback Data | ShellFix')
@section('page-title', 'Raw Feedback Data')
@section('page-description', 'Review and export individual feedback ratings.')

@section('content')
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('feedback.raw') }}" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label for="class_id" class="form-label">Class</label>
                    <select class="form-select" id="class_id" name="class_id">
                        <option value="">All classes</option>
                        @foreach ($filters['classes'] as $class)
                            <option value="{{ $class->id }}" @selected(($selected['class_id'] ?? '') == $class->id)>{{ $class->class_name }}</option>
                        @endforeach
                    </select>
                </div>
                @if (auth()->user()->isAdmin())
                    <div class="col-md-3">
                        <label for="lecturer_id" class="form-label">Lecturer</label>
                        <select class="form-select" id="lecturer_id" name="lecturer_id">
                            <option value="">All lecturers</option>
                            @foreach ($filters['lecturers'] as $lecturer)
                                <option value="{{ $lecturer->id }}" @selected(($selected['lecturer_id'] ?? '') == $lecturer->id)>{{ $lecturer->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="semester" class="form-label">Semester</label>
                        <select class="form-select" id="semester" name="semester">
                            <option value="">All</option>
                            @foreach ($filters['semesters'] as $semester)
                                <option value="{{ $semester }}" @selected(($selected['semester'] ?? '') === $semester)>{{ $semester }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div class="col-md-2">
                    <label for="category" class="form-label">Category</label>
                    <select class="form-select" id="category" name="category">
                        <option value="">All</option>
                        @foreach ($filters['categories'] as $category)
                            <option value="{{ $category }}" @selected(($selected['category'] ?? '') === $category)>{{ ucfirst($category) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                    <a href="{{ route('feedback.raw.export', request()->query()) }}" class="btn btn-outline-success">CSV</a>
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
                            <th>Student</th>
                            <th>Matric No</th>
                            <th>Class</th>
                            <th>Question</th>
                            <th>Category</th>
                            <th>Rating</th>
                            <th>Submitted Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($answers as $answer)
                            @php
                                $classes = $answer->user->enrolledClasses;
                                if (($selected['class_id'] ?? null)) {
                                    $classes = $classes->where('id', (int) $selected['class_id']);
                                }
                                if (auth()->user()->isLecturer()) {
                                    $classes = $classes->where('lecturer_id', auth()->id());
                                }
                                if (auth()->user()->isAdmin() && ($selected['lecturer_id'] ?? null)) {
                                    $classes = $classes->where('lecturer_id', (int) $selected['lecturer_id']);
                                }
                            @endphp
                            <tr>
                                <td>{{ $answer->user->name }}</td>
                                <td>{{ $answer->user->matric_no ?? $answer->user->registration_no ?? '-' }}</td>
                                <td>{{ $classes->pluck('class_name')->join(', ') ?: '-' }}</td>
                                <td>{{ $answer->question->question_text }}</td>
                                <td class="text-capitalize">{{ $answer->question->category }}</td>
                                <td>{{ $answer->rating }}</td>
                                <td>{{ $answer->created_at?->format('Y-m-d H:i') ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-secondary py-4">No feedback data found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
