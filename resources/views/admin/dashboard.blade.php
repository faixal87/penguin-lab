@extends('layouts.app')

@section('title', 'Admin Dashboard | ShellFix')
@section('page-title', 'Admin Dashboard')
@section('page-description', 'Review users, classes, login logs, and exam results.')

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
            <h2 class="h5">Users</h2>
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
                            <th>User Agent</th>
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
                                <td class="text-truncate" style="max-width: 240px;">{{ $user->last_user_agent ?? '-' }}</td>
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
                                <td>Set {{ $result->set_no }}</td>
                                <td>{{ $result->answered_count }}</td>
                                <td>{{ $result->total_score }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm" id="login-logs">
        <div class="card-body">
            <div class="d-flex justify-content-between gap-3 flex-wrap align-items-end mb-3">
                <h2 class="h5 mb-0">Login Logs</h2>
                <form method="GET" action="{{ route('dashboard') }}" class="row g-2 align-items-end">
                    <div class="col-auto">
                        <label for="login_log_per_page" class="form-label small mb-1">Rows</label>
                        <select class="form-select form-select-sm" id="login_log_per_page" name="login_log_per_page">
                            @foreach ([10, 20, 50] as $size)
                                <option value="{{ $size }}" @selected($loginLogPerPage === $size)>{{ $size }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-auto">
                        <label for="login_log_search" class="form-label small mb-1">Search</label>
                        <input type="search" class="form-control form-control-sm" id="login_log_search" name="login_log_search" value="{{ $loginLogSearch }}" placeholder="Name, email, IP, browser">
                    </div>
                    <div class="col-auto">
                        <button class="btn btn-sm btn-primary">Filter</button>
                        <a href="{{ route('dashboard') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
                    </div>
                </form>
            </div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>IP Address</th>
                            <th>Browser</th>
                            <th>Platform</th>
                            <th>Logged In</th>
                            <th>User Agent</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($loginLogs as $log)
                            <tr>
                                <td>{{ $log->user?->name ?? '-' }}</td>
                                <td>{{ $log->ip_address ?? '-' }}</td>
                                <td>{{ $log->browser ?? '-' }}</td>
                                <td>{{ $log->platform ?? '-' }}</td>
                                <td>{{ $log->logged_in_at?->format('Y-m-d H:i') ?? '-' }}</td>
                                <td class="text-truncate" style="max-width: 260px;">{{ $log->user_agent ?? '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-between align-items-center mt-3">
                <div class="text-secondary small">
                    Showing {{ $loginLogs->firstItem() ?? 0 }} to {{ $loginLogs->lastItem() ?? 0 }} of {{ $loginLogs->total() }} login records
                </div>
                {{ $loginLogs->links() }}
            </div>
        </div>
    </div>
@endsection
