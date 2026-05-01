@extends('layouts.app')

@section('title', 'Edit Class | ShellFix')
@section('page-title', 'Edit Class')
@section('page-description', 'Update class details.')

@section('content')
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ auth()->user()->isAdmin() ? route('admin.classes.update', $class) : route('lecturer.classes.update', $class) }}" class="row g-3">
                @csrf
                @method('PUT')

                <div class="col-md-5">
                    <label for="class_name" class="form-label">Class Name</label>
                    <input type="text" class="form-control @error('class_name') is-invalid @enderror" id="class_name" name="class_name" value="{{ old('class_name', $class->class_name) }}" required>
                    @error('class_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label for="course_code" class="form-label">Course Code</label>
                    <input type="text" class="form-control @error('course_code') is-invalid @enderror" id="course_code" name="course_code" value="{{ old('course_code', $class->course_code) }}" required>
                    @error('course_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                @if (auth()->user()->isAdmin())
                    <div class="col-md-3">
                        <label for="lecturer_id" class="form-label">Lecturer</label>
                        <select class="form-select @error('lecturer_id') is-invalid @enderror" id="lecturer_id" name="lecturer_id" required>
                            @foreach ($lecturers as $lecturer)
                                <option value="{{ $lecturer->id }}" @selected(old('lecturer_id', $class->lecturer_id) == $lecturer->id)>{{ $lecturer->name }}</option>
                            @endforeach
                        </select>
                        @error('lecturer_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                @endif

                <div class="col-12">
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                    <a href="{{ auth()->user()->isAdmin() ? route('admin.classes.index') : route('lecturer.classes.show', $class) }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
