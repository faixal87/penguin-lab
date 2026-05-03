@extends('layouts.app')

@section('title', 'Terminal Settings | ShellFix')
@section('page-title', 'Terminal Settings')
@section('page-description', 'Manage terminal status and preview Docker/Guacamole automation commands.')

@section('content')
    @if (session('status'))
        <div class="alert alert-success border-0 shadow-sm">{{ session('status') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger border-0 shadow-sm">{{ session('error') }}</div>
    @endif

    @if ($commandPreview)
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between gap-3 flex-wrap mb-3">
                    <div>
                        <h2 class="h5 mb-1">Command Preview</h2>
                        <p class="text-secondary mb-0">
                            {{ ucfirst($commandPreview['type']) }} command for {{ $commandPreview['student'] }}.
                            Executed: {{ $commandPreview['executed'] ? 'Yes' : 'No' }}
                        </p>
                    </div>
                    <span class="badge {{ ($commandPreview['success'] ?? true) ? 'text-bg-secondary' : 'text-bg-danger' }} align-self-start">{{ $commandPreview['message'] }}</span>
                </div>
                <pre class="terminal-command-preview mb-0"><code>{{ $commandPreview['command'] }}</code></pre>
                @if (! empty($commandPreview['output']))
                    <h3 class="h6 mt-3">SSH Output</h3>
                    <pre class="terminal-command-preview mb-0"><code>{{ $commandPreview['output'] }}</code></pre>
                @endif
            </div>
        </div>
    @endif

    <div class="row g-4">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm stat-card h-100">
                <div class="card-body">
                    <h2 class="h6 text-secondary">Students With Terminal Enabled</h2>
                    <p class="display-6 fw-semibold mb-0">{{ $enabledCount }} / {{ $studentCount }}</p>
                    <div class="text-secondary small mt-2">
                        Automation: {{ $automationEnabled ? 'Enabled: SSH/Docker commands can run' : 'Preview mode' }}
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h5">Global Student Access</h2>
                    <p class="text-secondary">This bulk setting updates the per-user terminal flag for all student accounts.</p>
                    <div class="d-flex gap-2 flex-wrap">
                        <form method="POST" action="{{ route('admin.terminal.settings') }}">
                            @csrf
                            <input type="hidden" name="action" value="enable_all_students">
                            <button type="submit" class="btn btn-primary">Enable All Students</button>
                        </form>
                        <form method="POST" action="{{ route('admin.terminal.settings') }}">
                            @csrf
                            <input type="hidden" name="action" value="disable_all_students">
                            <button type="submit" class="btn btn-outline-warning">Disable All Students</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="alert alert-info border-0 shadow-sm mt-4">
        Per-user terminal access can also be managed from Manage Users by editing a user account.
    </div>

    <div class="card border-0 shadow-sm mt-4">
        <div class="card-body">
            <h2 class="h5">Student Terminal Status</h2>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Matric / Registration</th>
                            <th>Linux Username</th>
                            <th>Guacamole Username</th>
                            <th>Classes</th>
                            <th>Access</th>
                            <th>Container Status</th>
                            <th>Guacamole</th>
                            <th>Last Started</th>
                            <th>Last Stopped</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($students as $student)
                            @php
                                $classTerminalEnabled = $student->enrolledClasses->contains(fn ($class) => $class->terminal_enabled);
                                $terminalAccess = $student->terminal_enabled || $classTerminalEnabled;
                                $guacamoleStatus = $student->guacamole_connection_status ?? 'Not synced';
                            @endphp
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $student->name }}</div>
                                    <div class="text-secondary small">{{ $student->email }}</div>
                                </td>
                                <td>{{ $student->matric_no ?? $student->registration_no ?? '-' }}</td>
                                <td>{{ $student->linux_username ?: 'Auto-generated on preview' }}</td>
                                <td>{{ $student->linux_username ?: 'Auto-generated on sync' }}</td>
                                <td>{{ $student->enrolledClasses->pluck('class_name')->join(', ') ?: '-' }}</td>
                                <td>
                                    <span class="badge {{ $terminalAccess ? 'text-bg-success' : 'text-bg-secondary' }}">
                                        {{ $terminalAccess ? 'Enabled' : 'Disabled' }}
                                    </span>
                                </td>
                                <td>{{ $student->container_status ?: 'not_started' }}</td>
                                <td>
                                    <span class="badge {{ $guacamoleStatus === 'Synced' ? 'text-bg-success' : ($guacamoleStatus === 'Failed' ? 'text-bg-danger' : 'text-bg-secondary') }}">
                                        {{ $guacamoleStatus }}
                                    </span>
                                </td>
                                <td>{{ $student->terminal_last_started_at?->format('Y-m-d H:i') ?? '-' }}</td>
                                <td>{{ $student->terminal_last_stopped_at?->format('Y-m-d H:i') ?? '-' }}</td>
                                <td class="text-end">
                                    <div class="d-flex gap-2 justify-content-end flex-wrap">
                                        <form method="POST" action="{{ route('admin.terminal.preview', $student) }}">
                                            @csrf
                                            <input type="hidden" name="command_type" value="start">
                                            <button type="submit" class="btn btn-sm btn-outline-primary">Preview Start</button>
                                        </form>
                                        <form method="POST" action="{{ route('admin.terminal.preview', $student) }}">
                                            @csrf
                                            <input type="hidden" name="command_type" value="stop">
                                            <button type="submit" class="btn btn-sm btn-outline-warning">Preview Stop</button>
                                        </form>
                                        <form method="POST" action="{{ route('admin.terminal.run', $student) }}">
                                            @csrf
                                            <input type="hidden" name="command_type" value="start">
                                            <button type="submit" class="btn btn-sm btn-primary" onclick="return confirm('Start this student terminal on the remote server?')">Start</button>
                                        </form>
                                        <form method="POST" action="{{ route('admin.terminal.run', $student) }}">
                                            @csrf
                                            <input type="hidden" name="command_type" value="stop">
                                            <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Stop this student terminal on the remote server?')">Stop</button>
                                        </form>
                                        <form method="POST" action="{{ route('admin.terminal.sync-guacamole', $student) }}">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-primary">Sync Guacamole Connection</button>
                                        </form>
                                        <form method="POST" action="{{ route('admin.terminal.reset-guacamole-password', $student) }}">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-warning" onclick="return confirm('Reset this student Guacamole password to the default password?')">Reset Guacamole Password</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="text-center text-secondary py-4">No student accounts found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
