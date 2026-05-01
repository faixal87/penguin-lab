@extends('layouts.app')

@section('title', 'Manage Users | ShellFix')
@section('page-title', 'Manage Users')
@section('page-description', 'Create, edit, and secure ShellFix user accounts.')

@section('content')
    @if (session('status'))
        <div class="alert alert-success border-0 shadow-sm">{{ session('status') }}</div>
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

            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Matric/Staff</th>
                            <th>Last Login</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($users as $user)
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $user->name }}</div>
                                    <div class="text-secondary small">{{ $user->email }}</div>
                                </td>
                                <td><span class="badge text-bg-secondary">{{ $user->role }}</span></td>
                                <td>{{ $user->status }}</td>
                                <td>{{ $user->matric_no ?? $user->staff_no ?? '-' }}</td>
                                <td>{{ $user->last_login_at?->format('Y-m-d H:i') ?? '-' }}</td>
                                <td class="text-end">
                                    <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-sm btn-outline-primary">View</a>
                                    <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                                    <a href="{{ route('admin.users.edit', $user) }}#reset-password" class="btn btn-sm btn-outline-warning">Reset Password</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-secondary py-4">No users found.</td>
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
