@extends('layouts.app')

@section('title', 'Manage Users | ShellFix')
@section('page-title', 'Manage Users')
@section('page-description', 'Create, edit, and secure ShellFix user accounts.')

@section('content')
    @if (session('status'))
        <div class="alert alert-success border-0 shadow-sm">{{ session('status') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger border-0 shadow-sm">{{ session('error') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger border-0 shadow-sm">
            {{ $errors->first() }}
        </div>
    @endif

    @if (session('delete_summary'))
        @php($summary = session('delete_summary'))
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <h2 class="h6 mb-3">Delete Summary</h2>
                <div class="row g-3 mb-3">
                    <div class="col-md-3"><strong>Deleted:</strong> {{ $summary['deleted'] }}</div>
                    <div class="col-md-3"><strong>Skipped:</strong> {{ $summary['skipped'] }}</div>
                </div>
                @if (! empty($summary['skipped_users']))
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Reason</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($summary['skipped_users'] as $skipped)
                                    <tr>
                                        <td>{{ $skipped['user'] }}</td>
                                        <td>{{ $skipped['reason'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    @endif

    <div class="d-flex gap-2 mb-4">
        <a href="{{ route('admin.users.create-lecturer') }}" class="btn btn-primary">Add Lecturer</a>
        <a href="{{ route('admin.users.create-student') }}" class="btn btn-outline-primary">Add Student</a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.users.index') }}" class="row g-3 align-items-end mb-4">
                <div class="col-md-3">
                    <label for="per_page" class="form-label">Users per page</label>
                    <select class="form-select" id="per_page" name="per_page">
                        @foreach ([10, 20, 50] as $size)
                            <option value="{{ $size }}" @selected($perPage === $size)>{{ $size }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6 ms-auto">
                    <label for="search" class="form-label">Search users</label>
                    <div class="input-group">
                        <input type="search" class="form-control" id="search" name="search" value="{{ $search }}" placeholder="Name, email, matric no, registration no">
                        <button type="submit" class="btn btn-primary">Search</button>
                        <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">Reset</a>
                    </div>
                </div>
            </form>

            <form id="batchDeleteForm" method="POST" action="{{ route('admin.users.batch-destroy', request()->only(['search', 'per_page', 'page'])) }}" onsubmit="return confirm('Delete selected users? This cannot be undone.')">
                @csrf
                @method('DELETE')
            </form>

            <div class="d-flex justify-content-between align-items-center gap-3 flex-wrap mb-3">
                <div class="text-secondary small">Select users to delete in bulk. Your own account and lecturers with classes will be skipped.</div>
                <button type="submit" form="batchDeleteForm" class="btn btn-outline-danger">Delete Selected</button>
            </div>

            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th style="width: 42px;">
                                <input type="checkbox" class="form-check-input" id="selectAllUsers" aria-label="Select all users">
                            </th>
                            <th>User</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Terminal</th>
                            <th>Matric/Staff</th>
                            <th>Last Login</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($users as $user)
                            <tr>
                                <td>
                                    <input type="checkbox" class="form-check-input user-select-checkbox" form="batchDeleteForm" name="user_ids[]" value="{{ $user->id }}" aria-label="Select {{ $user->name }}" @disabled(auth()->id() === $user->id)>
                                </td>
                                <td>
                                    <div class="fw-semibold">{{ $user->name }}</div>
                                    <div class="text-secondary small">{{ $user->email }}</div>
                                </td>
                                <td><span class="badge text-bg-secondary">{{ $user->role }}</span></td>
                                <td>{{ $user->status }}</td>
                                <td>
                                    <span class="badge {{ $user->terminal_enabled ? 'text-bg-success' : 'text-bg-secondary' }}">
                                        {{ $user->terminal_enabled ? 'Enabled' : 'Disabled' }}
                                    </span>
                                </td>
                                <td>{{ $user->matric_no ?? $user->staff_no ?? '-' }}</td>
                                <td>{{ $user->last_login_at?->format('Y-m-d H:i') ?? '-' }}</td>
                                <td class="text-end">
                                    <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-sm btn-outline-primary">View</a>
                                    <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                                    <a href="{{ route('admin.users.edit', $user) }}#reset-password" class="btn btn-sm btn-outline-warning">Reset Password</a>
                                    <form method="POST" action="{{ route('admin.users.destroy', array_merge(['user' => $user->id], request()->only(['search', 'per_page', 'page']))) }}" class="d-inline" onsubmit="return confirm('Delete this user? This cannot be undone.')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" @disabled(auth()->id() === $user->id)>Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-secondary py-4">No users found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-between align-items-center mt-4">
                <div class="text-secondary small">
                    Showing {{ $users->firstItem() ?? 0 }} to {{ $users->lastItem() ?? 0 }} of {{ $users->total() }} users
                </div>
                {{ $users->links() }}
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const selectAll = document.getElementById('selectAllUsers');
            const checkboxes = Array.from(document.querySelectorAll('.user-select-checkbox:not(:disabled)'));

            if (!selectAll) {
                return;
            }

            selectAll.addEventListener('change', () => {
                checkboxes.forEach((checkbox) => {
                    checkbox.checked = selectAll.checked;
                });
            });

            checkboxes.forEach((checkbox) => {
                checkbox.addEventListener('change', () => {
                    selectAll.checked = checkboxes.length > 0 && checkboxes.every((item) => item.checked);
                    selectAll.indeterminate = checkboxes.some((item) => item.checked) && !selectAll.checked;
                });
            });
        });
    </script>
@endpush
