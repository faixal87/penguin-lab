@extends('layouts.app')

@section('title', 'Terminal Settings | ShellFix')
@section('page-title', 'Terminal Settings')
@section('page-description', 'Enable or disable Guacamole terminal access for your classes.')

@section('content')
    @if (session('status'))
        <div class="alert alert-success border-0 shadow-sm">{{ session('status') }}</div>
    @endif

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Class</th>
                            <th>Course</th>
                            <th>Students</th>
                            <th>Status</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($classes as $class)
                            <tr>
                                <td class="fw-semibold">{{ $class->class_name }}</td>
                                <td>{{ $class->course_code }}</td>
                                <td>{{ $class->students_count }}</td>
                                <td>
                                    <span class="badge {{ $class->terminal_enabled ? 'text-bg-success' : 'text-bg-secondary' }}">
                                        {{ $class->terminal_enabled ? 'Enabled' : 'Disabled' }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <form method="POST" action="{{ route('lecturer.terminal.settings') }}">
                                        @csrf
                                        <input type="hidden" name="class_id" value="{{ $class->id }}">
                                        <input type="hidden" name="terminal_enabled" value="{{ $class->terminal_enabled ? 0 : 1 }}">
                                        <button type="submit" class="btn btn-sm {{ $class->terminal_enabled ? 'btn-outline-warning' : 'btn-primary' }}">
                                            {{ $class->terminal_enabled ? 'Disable' : 'Enable' }}
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-secondary py-4">No classes found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
