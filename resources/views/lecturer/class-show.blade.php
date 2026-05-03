@extends('layouts.app')

@section('title', $class->class_name . ' | ShellFix')
@section('page-title', $class->class_name)
@section('page-description', $class->course_code . ' student enrollment.')

@section('content')
    @if (session('status'))
        <div class="alert alert-success border-0 shadow-sm">{{ session('status') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger border-0 shadow-sm">{{ session('error') }}</div>
    @endif

    @if (session('import_summary'))
        @php($summary = session('import_summary'))
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <h2 class="h5">Import Summary</h2>
                <div class="row g-3 mb-3">
                    <div class="col-md-4"><strong>Total rows:</strong> {{ $summary['total'] }}</div>
                    <div class="col-md-4"><strong>Success:</strong> {{ $summary['success'] }}</div>
                    <div class="col-md-4"><strong>Skipped:</strong> {{ $summary['skipped'] }}</div>
                </div>
                @if (! empty($summary['errors']))
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Row</th>
                                    <th>Reason</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($summary['errors'] as $error)
                                    <tr>
                                        <td>{{ $error['row'] }}</td>
                                        <td>{{ $error['reason'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    @endif

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center gap-3 flex-wrap">
                <div>
                    <h2 class="h5 mb-1">Class Terminal Access</h2>
                    <p class="text-secondary mb-0">
                        Status:
                        <span class="badge {{ $class->terminal_enabled ? 'text-bg-success' : 'text-bg-secondary' }}">
                            {{ $class->terminal_enabled ? 'Enabled' : 'Disabled' }}
                        </span>
                    </p>
                </div>
                <form method="POST" action="{{ auth()->user()->isAdmin() ? route('admin.terminal.class-settings') : route('lecturer.terminal.settings') }}">
                    @csrf
                    <input type="hidden" name="class_id" value="{{ $class->id }}">
                    <input type="hidden" name="terminal_enabled" value="{{ $class->terminal_enabled ? 0 : 1 }}">
                    <button type="submit" class="btn {{ $class->terminal_enabled ? 'btn-outline-warning' : 'btn-primary' }}">
                        {{ $class->terminal_enabled ? 'Disable Terminal' : 'Enable Terminal' }}
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="h5 mb-0">Upload Student CSV</h2>
                <div class="d-flex gap-2">
                    <a href="{{ auth()->user()->isAdmin() ? route('admin.classes.edit', $class) : route('lecturer.classes.edit', $class) }}" class="btn btn-sm btn-outline-secondary">Edit Class</a>
                    <form method="POST" action="{{ auth()->user()->isAdmin() ? route('admin.classes.destroy', $class) : route('lecturer.classes.destroy', $class) }}">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this class?')">Delete</button>
                    </form>
                </div>
            </div>
            <form method="POST" action="{{ auth()->user()->isAdmin() ? route('admin.classes.students.upload', $class) : route('lecturer.classes.students.upload', $class) }}" enctype="multipart/form-data">
                @csrf
                <div class="mb-3">
                    <label for="students_csv" class="form-label">CSV file</label>
                    <input type="file" class="form-control @error('students_csv') is-invalid @enderror" id="students_csv" name="students_csv" accept=".csv,text/csv" required>
                    @error('students_csv')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <button type="submit" class="btn btn-primary">Upload CSV</button>
                <a href="{{ auth()->user()->isAdmin() ? route('admin.classes.index') : route('dashboard') }}" class="btn btn-outline-secondary">Back</a>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <h2 class="h5">Students</h2>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Matric No</th>
                            <th>Email</th>
                            <th>Status</th>
                            <th>Linux Username</th>
                            <th>Guacamole Username</th>
                            <th>Terminal</th>
                            <th>Container Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($class->students as $student)
                            <tr>
                                <td>{{ $student->name }}</td>
                                <td>{{ $student->matric_no }}</td>
                                <td>{{ $student->email }}</td>
                                <td>{{ $student->status }}</td>
                                <td>{{ $student->linux_username ?: '-' }}</td>
                                <td>{{ $student->linux_username ?: '-' }}</td>
                                <td>
                                    <span class="badge {{ ($student->terminal_enabled || $class->terminal_enabled) ? 'text-bg-success' : 'text-bg-secondary' }}">
                                        {{ ($student->terminal_enabled || $class->terminal_enabled) ? 'Enabled' : 'Disabled' }}
                                    </span>
                                </td>
                                <td>{{ $student->container_status ?: 'not_started' }}</td>
                                <td class="text-end">
                                    @if ($class->terminal_enabled)
                                        <div class="d-flex gap-2 justify-content-end flex-wrap">
                                            <form method="POST" action="{{ route('lecturer.terminal.run', $student) }}">
                                                @csrf
                                                <input type="hidden" name="command_type" value="start">
                                                <button type="submit" class="btn btn-sm btn-primary" onclick="return confirm('Start this student terminal?')">Start</button>
                                            </form>
                                            <form method="POST" action="{{ route('lecturer.terminal.run', $student) }}">
                                                @csrf
                                                <input type="hidden" name="command_type" value="stop">
                                                <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Stop this student terminal?')">Stop</button>
                                            </form>
                                        </div>
                                    @else
                                        <span class="text-secondary small">Class terminal disabled</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-secondary py-4">No students enrolled yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
