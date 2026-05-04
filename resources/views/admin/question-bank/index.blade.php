@extends('layouts.app')

@section('title', 'Question Bank | ShellFix')
@section('page-title', 'Question Bank')
@section('page-description', 'Create and manage reusable Linux command questions.')

@section('content')
    @if (session('status'))<div class="alert alert-success border-0 shadow-sm">{{ session('status') }}</div>@endif
    <div class="d-flex justify-content-between gap-3 mb-4">
        <a href="{{ route($routePrefix . '.question-bank.create') }}" class="btn btn-primary">Create Question</a>
        <form method="GET" class="d-flex gap-2">
            <input type="search" name="search" class="form-control" value="{{ request('search') }}" placeholder="Search title/category">
            <button class="btn btn-outline-primary">Search</button>
        </form>
    </div>
    <div class="card border-0 shadow-sm"><div class="card-body table-responsive">
        <table class="table align-middle mb-0">
            <thead><tr><th>Question</th><th>Category</th><th>Difficulty</th><th>Score</th><th>Owner</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
            <tbody>
            @forelse($questions as $question)
                <tr>
                    <td><div class="fw-semibold">{{ $question->title }}</div><div class="text-secondary small">{{ Str::limit($question->description, 90) }}</div></td>
                    <td>{{ $question->category ?? '-' }}</td>
                    <td>{{ $question->difficulty ?? '-' }}</td>
                    <td>{{ $question->score }}</td>
                    <td>
                        @if($question->created_by)
                            <span class="badge text-bg-info">Lecturer</span>
                        @else
                            <span class="badge text-bg-primary">Admin shared</span>
                        @endif
                    </td>
                    <td><span class="badge {{ $question->is_active ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $question->is_active ? 'Active' : 'Inactive' }}</span></td>
                    <td class="text-end">
                        @if(auth()->user()->isAdmin() || $question->created_by === auth()->id())
                            <a href="{{ route($routePrefix . '.question-bank.edit', $question) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                            <form method="POST" action="{{ route($routePrefix . '.question-bank.destroy', $question) }}" class="d-inline" onsubmit="return confirm('Delete this question?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">Delete</button></form>
                        @else
                            <span class="text-secondary small">Shared read-only</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-secondary py-4">No questions yet.</td></tr>
            @endforelse
            </tbody>
        </table>
        <div class="mt-3">{{ $questions->links() }}</div>
    </div></div>
@endsection
