@extends('layouts.app')

@section('title', 'Manage Classes | ShellFix')
@section('page-title', 'Manage Classes')
@section('page-description', 'Select a class to manage its name, semester, and students.')

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
            <a href="{{ route('admin.semesters.index') }}" class="btn btn-sm btn-outline-primary ms-2">Semester Settings</a>
        @endunless
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <h2 class="h5">Create Class</h2>
            <form method="POST" action="{{ route('admin.classes.store') }}" class="row g-3">
                @csrf
                <div class="col-md-4">
                    <label for="class_name" class="form-label">Class Name</label>
                    <input type="text" class="form-control @error('class_name') is-invalid @enderror" id="class_name" name="class_name" value="{{ old('class_name') }}" required>
                    @error('class_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label for="course_code" class="form-label">Course Code</label>
                    <input type="text" class="form-control @error('course_code') is-invalid @enderror" id="course_code" name="course_code" value="{{ old('course_code') }}" required>
                    @error('course_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label for="lecturer_id" class="form-label">Lecturer</label>
                    <select class="form-select @error('lecturer_id') is-invalid @enderror" id="lecturer_id" name="lecturer_id" required>
                        <option value="">Select lecturer</option>
                        @foreach ($lecturers as $lecturer)
                            <option value="{{ $lecturer->id }}" @selected(old('lecturer_id') == $lecturer->id)>{{ $lecturer->name }}</option>
                        @endforeach
                    </select>
                    @error('lecturer_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100" @disabled(! $currentSemester)>Create</button>
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
                            <th>Class</th>
                            <th>Semester</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($classes as $class)
                            <tr>
                                <td>
                                    <a href="{{ route('admin.classes.show', $class) }}" class="fw-semibold text-decoration-none">
                                        {{ $class->class_name }}
                                    </a>
                                </td>
                                <td>{{ $class->semester?->name ?? '-' }}</td>
                                <td class="text-end">
                                    <a href="{{ route('admin.classes.show', $class) }}" class="btn btn-sm btn-outline-primary">Open</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center text-secondary py-4">No classes found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
