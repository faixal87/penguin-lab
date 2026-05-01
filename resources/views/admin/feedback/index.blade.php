@extends('layouts.app')

@section('title', 'Feedback Questions | ShellFix')
@section('page-title', 'Feedback Questions')
@section('page-description', 'Manage Likert-scale course feedback questions.')

@section('content')
    @if (session('status'))
        <div class="alert alert-success border-0 shadow-sm">{{ session('status') }}</div>
    @endif

    <div class="mb-4">
        <a href="{{ route('admin.feedback.create') }}" class="btn btn-primary">Add Question</a>
        <a href="{{ route('feedback.summary') }}" class="btn btn-outline-primary">View Summary</a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Question</th>
                            <th>Category</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($questions as $question)
                            <tr>
                                <td>{{ $question->question_text }}</td>
                                <td class="text-capitalize">{{ $question->category }}</td>
                                <td>
                                    <span class="badge {{ $question->is_active ? 'text-bg-success' : 'text-bg-secondary' }}">
                                        {{ $question->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('admin.feedback.edit', $question) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                    <form method="POST" action="{{ route('admin.feedback.toggle', $question) }}" class="d-inline">
                                        @csrf
                                        @method('PUT')
                                        <button type="submit" class="btn btn-sm btn-outline-secondary">{{ $question->is_active ? 'Deactivate' : 'Activate' }}</button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.feedback.destroy', $question) }}" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this feedback question?')">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
