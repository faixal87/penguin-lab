@extends('layouts.app')

@section('title', 'Manage Notifications | ShellFix')
@section('page-title', 'Manage Notifications')
@section('page-description', 'Send targeted alerts to users, roles, and classes.')

@section('content')
    @if (session('status'))
        <div class="alert alert-success border-0 shadow-sm">{{ session('status') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger border-0 shadow-sm">{{ session('error') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger border-0 shadow-sm">{{ $errors->first() }}</div>
    @endif

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <h2 class="h5 mb-3">Create Notification</h2>
            <form method="POST" action="{{ route('notifications.store') }}" class="row g-3">
                @csrf
                <div class="col-md-6">
                    <label class="form-label" for="title">Title</label>
                    <input type="text" class="form-control" id="title" name="title" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="target_type">Target</label>
                    <select class="form-select" id="target_type" name="target_type" required>
                        @if (auth()->user()->isAdmin())
                            <option value="all">All users</option>
                            <option value="role">Role</option>
                        @endif
                        <option value="class">Selected class</option>
                        <option value="user">Selected user</option>
                    </select>
                </div>
                @if (auth()->user()->isAdmin())
                    <div class="col-md-4">
                        <label class="form-label" for="target_role">Role</label>
                        <select class="form-select" id="target_role" name="target_role">
                            <option value="student">Students</option>
                            <option value="lecturer">Lecturers</option>
                            <option value="admin">Admins</option>
                        </select>
                    </div>
                @endif
                <div class="col-md-4">
                    <label class="form-label" for="target_class_id">Class</label>
                    <select class="form-select" id="target_class_id" name="target_class_id">
                        <option value="">Select class</option>
                        @foreach ($classes as $class)
                            <option value="{{ $class->id }}">{{ $class->class_name }} {{ $class->course_code ? '(' . $class->course_code . ')' : '' }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="target_user_id">User</label>
                    <select class="form-select" id="target_user_id" name="target_user_id">
                        <option value="">Select user</option>
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label" for="message">Message</label>
                    <textarea class="form-control" id="message" name="message" rows="3" required></textarea>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">Send Notification</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                <h2 class="h5 mb-0">Sent Notifications</h2>
                <form id="bulkNotificationDeleteForm" method="POST" action="{{ route('notifications.batch-destroy') }}" onsubmit="return confirm('Delete selected notifications? This cannot be undone.');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-outline-danger">Delete Selected</button>
                </form>
            </div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th style="width: 48px;">
                                <input type="checkbox" class="form-check-input" id="selectAllNotifications" aria-label="Select all notifications">
                            </th>
                            <th>Notification</th>
                            <th>Target</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($notifications as $notification)
                            <tr>
                                <td>
                                    <input
                                        type="checkbox"
                                        class="form-check-input notification-select-checkbox"
                                        form="bulkNotificationDeleteForm"
                                        name="notification_ids[]"
                                        value="{{ $notification->id }}"
                                        aria-label="Select notification {{ $notification->title }}"
                                    >
                                </td>
                                <td>
                                    <div class="fw-semibold">{{ $notification->title }}</div>
                                    <div class="text-secondary small">{{ $notification->message }}</div>
                                </td>
                                <td>
                                    @if ($notification->target_type === 'all')
                                        All users
                                    @elseif ($notification->target_type === 'role')
                                        Role: {{ $notification->target_role }}
                                    @elseif ($notification->target_type === 'class')
                                        Class: {{ $notification->targetClass?->class_name ?? 'Deleted class' }}
                                    @else
                                        User: {{ $notification->targetUser?->name ?? 'Deleted user' }}
                                    @endif
                                </td>
                                <td>
                                    <span class="badge {{ $notification->is_active ? 'text-bg-success' : 'text-bg-secondary' }}">
                                        {{ $notification->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td>{{ $notification->created_at?->format('Y-m-d H:i') ?? '-' }}</td>
                                <td class="text-end">
                                    <form method="POST" action="{{ route('notifications.toggle', $notification) }}" class="d-inline">
                                        @csrf
                                        @method('PUT')
                                        <button type="submit" class="btn btn-sm btn-outline-warning">{{ $notification->is_active ? 'Deactivate' : 'Activate' }}</button>
                                    </form>
                                    <form method="POST" action="{{ route('notifications.destroy', $notification) }}" class="d-inline" onsubmit="return confirm('Delete this notification? This cannot be undone.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-secondary py-4">No notifications created yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const selectAll = document.getElementById('selectAllNotifications');
            const checkboxes = Array.from(document.querySelectorAll('.notification-select-checkbox'));

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
