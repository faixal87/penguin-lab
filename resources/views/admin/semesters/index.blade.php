@extends('layouts.app')

@section('title', 'Semester Settings | ShellFix')
@section('page-title', 'Semester Settings')
@section('page-description', 'Set the active academic semester for classes and rankings.')

@section('content')
    @if (session('status'))<div class="alert alert-success border-0 shadow-sm">{{ session('status') }}</div>@endif
    @if ($errors->any())<div class="alert alert-danger border-0 shadow-sm">{{ $errors->first() }}</div>@endif

    <div class="alert alert-info border-0 shadow-sm">
        Current semester: <strong>{{ $currentSemester?->name ?? 'Not set' }}</strong>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <h2 class="h5">Create Semester / Session</h2>
            <form method="POST" action="{{ route('admin.semesters.store') }}" class="row g-3">
                @csrf
                <div class="col-md-4">
                    <label class="form-label" for="name">Semester Name</label>
                    <input class="form-control" id="name" name="name" placeholder="SESI I 2025-2026" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="start_date">Start Date</label>
                    <input type="date" class="form-control" id="start_date" name="start_date">
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="end_date">End Date</label>
                    <input type="date" class="form-control" id="end_date" name="end_date">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <label class="form-check mb-2">
                        <input type="checkbox" class="form-check-input" name="is_current" value="1">
                        <span class="form-check-label">Set current</span>
                    </label>
                </div>
                <div class="col-12"><button class="btn btn-primary">Save Semester</button></div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body table-responsive">
            <table class="table align-middle mb-0">
                <thead><tr><th>Name</th><th>Dates</th><th>Status</th><th class="text-end">Action</th></tr></thead>
                <tbody>
                @forelse($semesters as $semester)
                    <tr>
                        <td class="fw-semibold">{{ $semester->name }}</td>
                        <td>{{ $semester->start_date?->format('Y-m-d') ?? '-' }} to {{ $semester->end_date?->format('Y-m-d') ?? '-' }}</td>
                        <td><span class="badge {{ $semester->is_current ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $semester->is_current ? 'Current' : 'Inactive' }}</span></td>
                        <td class="text-end">
                            @unless($semester->is_current)
                                <form method="POST" action="{{ route('admin.semesters.current', $semester) }}">
                                    @csrf
                                    @method('PUT')
                                    <button class="btn btn-sm btn-outline-primary">Set Current</button>
                                </form>
                            @endunless
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center text-secondary py-4">No semesters created yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
