@extends('layouts.app')

@section('title', 'Question Sets | ShellFix')
@section('page-title', 'Question Sets')
@section('page-description', 'Build assignable 100-mark exam sets.')

@section('content')
    @if (session('status'))<div class="alert alert-success border-0 shadow-sm">{{ session('status') }}</div>@endif
    <a href="{{ route($routePrefix . '.question-sets.create') }}" class="btn btn-primary mb-4">Create Question Set</a>
    <div class="card border-0 shadow-sm"><div class="card-body table-responsive">
        <table class="table align-middle mb-0">
            <thead><tr><th>Set</th><th>Owner</th><th>Questions</th><th>Total</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
            <tbody>
            @forelse($sets as $set)
                @php($total = $set->currentTotal())
                <tr>
                    <td><div class="fw-semibold">{{ $set->name }}</div><div class="text-secondary small">{{ $set->description }}</div></td>
                    <td>
                        @if($set->created_by)
                            <span class="badge text-bg-info">Lecturer</span>
                        @else
                            <span class="badge text-bg-primary">Admin shared</span>
                        @endif
                    </td>
                    <td>{{ $set->setQuestions->count() }}</td>
                    <td><span class="badge {{ $total === 100 ? 'text-bg-success' : 'text-bg-warning' }}">{{ $total }}/100</span></td>
                    <td><span class="badge {{ $set->is_active ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $set->is_active ? 'Active' : 'Inactive' }}</span></td>
                    <td class="text-end">
                        <a href="{{ route($routePrefix . '.question-sets.edit', $set) }}" class="btn btn-sm btn-outline-primary">Manage</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center text-secondary py-4">No question sets yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div></div>
@endsection
