@extends('layouts.app')

@section('title', 'Notification Center | ShellFix')
@section('page-title', 'Notification Center')
@section('page-description', 'Review alerts and course updates sent to your account.')

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
                            <th>Notification</th>
                            <th>Sent By</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($notifications as $notification)
                            @php($read = $notification->reads->first()?->read_at)
                            @php($dismissed = $notification->reads->first()?->dismissed_at)
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $notification->title }}</div>
                                    <div class="text-secondary small">{{ $notification->message }}</div>
                                </td>
                                <td>{{ $notification->sender?->name ?? 'System' }}</td>
                                <td>
                                    @if (! $read)
                                        <span class="badge text-bg-info">Unread</span>
                                    @elseif ($dismissed)
                                        <span class="badge text-bg-secondary">Dismissed</span>
                                    @else
                                        <span class="badge text-bg-success">Read</span>
                                    @endif
                                </td>
                                <td>{{ $notification->created_at?->format('Y-m-d H:i') ?? '-' }}</td>
                                <td class="text-end">
                                    <div class="d-flex gap-2 justify-content-end flex-wrap">
                                        @if ($notification->title === 'Course Feedback is now available' && auth()->user()->isStudent())
                                            <a href="{{ route('feedback.form') }}" class="btn btn-sm btn-primary">Open Feedback</a>
                                        @endif
                                        <form method="POST" action="{{ route('notifications.read', $notification) }}">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-primary">Mark Read</button>
                                        </form>
                                        <form method="POST" action="{{ route('notifications.dismiss', $notification) }}">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-secondary">Dismiss</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-secondary py-4">No notifications found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
