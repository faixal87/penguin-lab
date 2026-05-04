@extends('layouts.app')

@section('title', 'Feedback Control | ShellFix')
@section('page-title', 'Feedback Control')
@section('page-description', 'Enable or disable Course Feedback for targeted students.')

@section('content')
    @if (session('status'))
        <div class="alert alert-success border-0 shadow-sm">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger border-0 shadow-sm">{{ $errors->first() }}</div>
    @endif

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <h2 class="h5 mb-3">Set Feedback Availability</h2>
            <form method="POST" action="{{ route('feedback.control.store') }}" class="row g-3 align-items-end">
                @csrf
                <div class="col-md-3">
                    <label class="form-label" for="target_type">Target</label>
                    <select class="form-select" id="target_type" name="target_type" required>
                        @if (auth()->user()->isAdmin())
                            <option value="all">All students</option>
                            <option value="role">Student role</option>
                        @endif
                        <option value="class">Selected class</option>
                        <option value="user">Selected student</option>
                    </select>
                </div>
                @if (auth()->user()->isAdmin())
                    <input type="hidden" name="target_role" value="student">
                @endif
                <div class="col-md-3">
                    <label class="form-label" for="target_class_id">Class</label>
                    <select class="form-select" id="target_class_id" name="target_class_id">
                        <option value="">Select class</option>
                        @foreach ($classes as $class)
                            <option value="{{ $class->id }}">{{ $class->class_name }} {{ $class->course_code ? '(' . $class->course_code . ')' : '' }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="target_user_id">Student</label>
                    <select class="form-select" id="target_user_id" name="target_user_id">
                        <option value="">Select student</option>
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->matric_no ?? $user->registration_no ?? $user->email }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="is_enabled">Status</label>
                    <select class="form-select" id="is_enabled" name="is_enabled">
                        <option value="1">Enabled</option>
                        <option value="0">Disabled</option>
                    </select>
                </div>
                <div class="col-md-1">
                    <button type="submit" class="btn btn-primary w-100">Save</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <h2 class="h5 mb-3">Current Controls</h2>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Target</th>
                            <th>Created By</th>
                            <th>Status</th>
                            <th>Updated</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($controls as $control)
                            <tr>
                                <td>
                                    @if ($control->target_type === 'all')
                                        All students
                                    @elseif ($control->target_type === 'role')
                                        Role: {{ $control->target_role }}
                                    @elseif ($control->target_type === 'class')
                                        Class: {{ $control->targetClass?->class_name ?? 'Deleted class' }}
                                    @else
                                        Student: {{ $control->targetUser?->name ?? 'Deleted student' }}
                                    @endif
                                </td>
                                <td>{{ $control->creator?->name ?? 'System' }}</td>
                                <td>
                                    <span class="badge {{ $control->is_enabled ? 'text-bg-success' : 'text-bg-secondary' }}">
                                        {{ $control->is_enabled ? 'Enabled' : 'Disabled' }}
                                    </span>
                                </td>
                                <td>{{ $control->updated_at?->format('Y-m-d H:i') ?? '-' }}</td>
                                <td class="text-end">
                                    <form method="POST" action="{{ route('feedback.control.toggle', $control) }}">
                                        @csrf
                                        @method('PUT')
                                        <button type="submit" class="btn btn-sm btn-outline-warning">{{ $control->is_enabled ? 'Disable' : 'Enable' }}</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-secondary py-4">No feedback controls configured yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
