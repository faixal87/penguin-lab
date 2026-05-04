@extends('layouts.app')

@section('title', 'Assign Question Set | ShellFix')
@section('page-title', 'Assign Question Set')
@section('page-description', auth()->user()->isAdmin() ? 'Assign valid question sets to any class.' : 'Assign valid question sets to your classes.')

@section('content')
    @if (session('status'))<div class="alert alert-success border-0 shadow-sm">{{ session('status') }}</div>@endif
    @if (session('error'))<div class="alert alert-danger border-0 shadow-sm">{{ session('error') }}</div>@endif
    @if ($errors->any())<div class="alert alert-danger border-0 shadow-sm">{{ $errors->first() }}</div>@endif
    <div class="alert alert-info border-0 shadow-sm">
        Only active question sets with exactly 100 marks can be assigned. Student-specific assignments override class assignments.
    </div>

    <div class="card border-0 shadow-sm mb-4"><div class="card-body table-responsive">
        <h2 class="h5 mb-3">Class Assignments</h2>
        <table class="table align-middle mb-0">
            <thead><tr><th>Class</th><th>Current Sets</th><th>Assign Sets</th><th class="text-end">Action</th></tr></thead>
            <tbody>
            @forelse($classes as $class)
                <tr>
                    <td>
                        <div class="fw-semibold">{{ $class->class_name }}</div>
                        <div class="text-secondary small">{{ $class->course_code }} @if(auth()->user()->isAdmin()) | {{ $class->lecturer?->name ?? 'No lecturer' }} @endif</div>
                    </td>
                    <td>
                        @if($class->questionSets->isNotEmpty())
                            @foreach($class->questionSets as $currentSet)
                                @php($currentTotal = $currentSet->currentTotal())
                                <div class="mb-1">
                                    <span class="fw-semibold">{{ $currentSet->name }}</span>
                                    <span class="badge {{ $currentSet->is_active ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $currentSet->is_active ? 'Active' : 'Inactive' }}</span>
                                    <span class="badge {{ $currentTotal === 100 ? 'text-bg-success' : 'text-bg-warning' }}">{{ $currentTotal }}/100</span>
                                </div>
                            @endforeach
                        @else
                            <span class="text-secondary">No class set assigned</span>
                        @endif
                    </td>
                    <td>
                        <form method="POST" action="{{ route($routePrefix . '.classes.question-set.update', $class) }}">@csrf @method('PUT')
                            <div class="d-flex flex-column gap-1">
                                @foreach($sets as $set)
                                    @php($total = $set->currentTotal())
                                    @php($assignable = $set->is_active && $total === 100)
                                    @php($isCurrent = $class->questionSets->contains('id', $set->id))
                                    <label class="form-check">
                                        <input type="checkbox" class="form-check-input" name="question_set_ids[]" value="{{ $set->id }}" @checked($isCurrent) @disabled(! $assignable && ! $isCurrent)>
                                        <span class="form-check-label">{{ $set->name }} - {{ $total }}/100 - {{ $set->is_active ? 'Active' : 'Inactive' }}{{ $assignable ? '' : ' (cannot assign)' }}</span>
                                    </label>
                                @endforeach
                            </div>
                    </td>
                    <td class="text-end"><button class="btn btn-sm btn-primary">Save</button></form></td>
                </tr>
            @empty
                <tr><td colspan="4" class="text-center text-secondary py-4">No classes found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div></div>

    <div class="card border-0 shadow-sm"><div class="card-body table-responsive">
        <h2 class="h5 mb-3">Student-Specific Assignments</h2>
        <table class="table align-middle mb-0">
            <thead><tr><th>Student</th><th>Class</th><th>Assigned Sets</th><th class="text-end">Action</th></tr></thead>
            <tbody>
            @forelse($students as $student)
                <tr>
                    <td>
                        <div class="fw-semibold">{{ $student->name }}</div>
                        <div class="text-secondary small">{{ $student->matric_no ?? $student->registration_no ?? $student->email }}</div>
                    </td>
                    <td class="text-secondary small">{{ $student->enrolledClasses->pluck('class_name')->join(', ') ?: '-' }}</td>
                    <td>
                        <form method="POST" action="{{ route($routePrefix . '.students.question-sets.update', $student) }}">@csrf @method('PUT')
                            <div class="d-flex flex-column gap-1">
                                @foreach($sets as $set)
                                    @php($total = $set->currentTotal())
                                    @php($assignable = $set->is_active && $total === 100)
                                    @php($isCurrent = $student->assignedQuestionSets->contains('id', $set->id))
                                    <label class="form-check">
                                        <input type="checkbox" class="form-check-input" name="question_set_ids[]" value="{{ $set->id }}" @checked($isCurrent) @disabled(! $assignable && ! $isCurrent)>
                                        <span class="form-check-label">{{ $set->name }} - {{ $total }}/100 - {{ $set->is_active ? 'Active' : 'Inactive' }}{{ $assignable ? '' : ' (cannot assign)' }}</span>
                                    </label>
                                @endforeach
                            </div>
                    </td>
                    <td class="text-end"><button class="btn btn-sm btn-primary">Save</button></form></td>
                </tr>
            @empty
                <tr><td colspan="4" class="text-center text-secondary py-4">No students found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div></div>
@endsection
