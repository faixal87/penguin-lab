@extends('layouts.app')

@section('title', 'Activity Logs | ShellFix')
@section('page-title', 'Activity Logs')
@section('page-description', 'Grouped user activity records for login, logout, terminal, and learning actions.')

@section('content')
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.activity-logs.index') }}" class="row g-3 align-items-end">
                <div class="col-md-8">
                    <label for="activity_log_search" class="form-label">Search</label>
                    <input
                        type="search"
                        class="form-control"
                        id="activity_log_search"
                        name="activity_log_search"
                        value="{{ $search }}"
                        placeholder="Search name, email, activity, IP, browser, or platform"
                    >
                </div>
                <div class="col-md-4 d-flex gap-2">
                    <button class="btn btn-primary flex-fill">Search</button>
                    <a href="{{ route('admin.activity-logs.index') }}" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center gap-3 flex-wrap mb-3">
                <div>
                    <h2 class="h5 mb-1">Grouped Activities</h2>
                    <p class="text-secondary small mb-0">Showing {{ $totalLogs }} activity records, grouped by matching user name.</p>
                </div>
                <a href="{{ route('dashboard') }}" class="btn btn-sm btn-outline-primary">Back to Dashboard</a>
            </div>

            <div class="accordion shellfix-accordion" id="activityLogAccordion">
                @forelse ($groupedLogs as $userName => $logs)
                    @php($accordionId = 'activityUser' . $loop->index)
                    @php($firstLog = $logs->first())
                    <div class="accordion-item border-0 mb-3">
                        <h3 class="accordion-header" id="{{ $accordionId }}Heading">
                            <button class="accordion-button {{ $loop->first ? '' : 'collapsed' }}" type="button" data-bs-toggle="collapse" data-bs-target="#{{ $accordionId }}" aria-expanded="{{ $loop->first ? 'true' : 'false' }}" aria-controls="{{ $accordionId }}">
                                <span class="fw-semibold">{{ $userName }}</span>
                                <span class="badge text-bg-info ms-3">{{ $logs->count() }} activities</span>
                                @if ($firstLog?->user?->email)
                                    <span class="text-secondary small ms-3">{{ $firstLog->user->email }}</span>
                                @endif
                            </button>
                        </h3>
                        <div id="{{ $accordionId }}" class="accordion-collapse collapse {{ $loop->first ? 'show' : '' }}" aria-labelledby="{{ $accordionId }}Heading" data-bs-parent="#activityLogAccordion">
                            <div class="accordion-body">
                                <div class="table-responsive">
                                    <table class="table align-middle mb-0">
                                        <thead>
                                            <tr>
                                                <th>Activity</th>
                                                <th>IP Address</th>
                                                <th>Browser</th>
                                                <th>Platform</th>
                                                <th>Date / Time</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($logs as $log)
                                                <tr>
                                                    <td><span class="badge text-bg-secondary">{{ ucfirst($log->activity ?? 'login') }}</span></td>
                                                    <td>{{ $log->ip_address ?? '-' }}</td>
                                                    <td>{{ $log->browser ?? '-' }}</td>
                                                    <td>{{ $log->platform ?? '-' }}</td>
                                                    <td>{{ $log->logged_in_at?->format('Y-m-d H:i') ?? '-' }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-center text-secondary py-5">No activity logs found.</div>
                @endforelse
            </div>
        </div>
    </div>
@endsection
