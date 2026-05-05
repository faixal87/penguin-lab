@extends('layouts.app')

@section('title', 'Admin Dashboard | ShellFix')
@section('page-title', 'Admin Dashboard')
@section('page-description', 'Review users, classes, and exam results.')

@section('content')
    <div class="alert alert-info border-0 shadow-sm">
        Current semester: <strong>{{ $currentSemester?->name ?? 'Not set' }}</strong>
        <a href="{{ route('admin.semesters.index') }}" class="btn btn-sm btn-outline-primary ms-2">Semester Settings</a>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h2 class="h6 text-secondary">Users</h2>
                    <p class="display-6 fw-semibold mb-0">{{ $users->count() }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h2 class="h6 text-secondary">Classes</h2>
                    <p class="display-6 fw-semibold mb-0">{{ $classes->count() }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h2 class="h6 text-secondary">Exam Sessions</h2>
                    <p class="display-6 fw-semibold mb-0">{{ $examResults->count() }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center gap-3 flex-wrap mb-3">
                <h2 class="h5 mb-0">Users</h2>
                <a href="{{ route('admin.activity-logs.index') }}" class="btn btn-sm btn-outline-primary">Open Activity Logs</a>
            </div>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Matric No</th>
                            <th>Status</th>
                            <th>Last Login</th>
                            <th>IP</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($users as $user)
                            <tr>
                                <td>{{ $user->name }}</td>
                                <td>{{ $user->email }}</td>
                                <td><span class="badge text-bg-secondary">{{ $user->role }}</span></td>
                                <td>{{ $user->matric_no ?? '-' }}</td>
                                <td>{{ $user->status }}</td>
                                <td>{{ $user->last_login_at?->format('Y-m-d H:i') ?? '-' }}</td>
                                <td>{{ $user->last_login_ip ?? '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <h2 class="h5">Classes</h2>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>Class</th>
                            <th>Course</th>
                            <th>Lecturer</th>
                            <th>Students</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($classes as $class)
                            <tr>
                                <td>{{ $class->class_name }}</td>
                                <td>{{ $class->course_code }}</td>
                                <td>{{ $class->lecturer?->name ?? '-' }}</td>
                                <td>{{ $class->students->count() }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <h2 class="h5">Exam Results</h2>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Set</th>
                            <th>Answered</th>
                            <th>Total Score</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($examResults as $result)
                            <tr>
                                <td>{{ $result->user?->name ?? 'Unknown' }}</td>
                                <td>{{ $result->questionSet?->name ?? ($result->set_no ? 'Set ' . $result->set_no : 'Question Set') }}</td>
                                <td>{{ $result->answered_count }}</td>
                                <td>{{ $result->total_score }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

@endsection
