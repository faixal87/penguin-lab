@extends('layouts.app')

@section('title', 'Question Sets | ShellFix')
@section('page-title', 'Question Sets')
@section('page-description', 'Review generated scenario questions and expected commands.')

@section('content')
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('question-sets.index') }}" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label for="set_no" class="form-label">Question Set</label>
                    <select class="form-select" id="set_no" name="set_no">
                        @foreach ($sets as $setNo)
                            <option value="{{ $setNo }}" @selected($selectedSet === $setNo)>Set {{ $setNo }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="category" class="form-label">Topic / Category</label>
                    <select class="form-select" id="category" name="category">
                        <option value="">All topics</option>
                        @foreach ($categories as $item)
                            <option value="{{ $item }}" @selected($category === $item)>{{ $item }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="search" class="form-label">Search</label>
                    <input type="search" class="form-control" id="search" name="search" value="{{ $search }}" placeholder="Search title, description, topic, difficulty">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Apply</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Description</th>
                            <th>Topic / Category</th>
                            <th>Difficulty</th>
                            <th>Score</th>
                            <th>Hint 1</th>
                            <th>Hint 2</th>
                            <th>Expected Command / Answer</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($questions as $question)
                            <tr>
                                <td class="fw-semibold">{{ $question->title }}</td>
                                <td>{{ $question->description }}</td>
                                <td>{{ $question->question_type ?? '-' }}</td>
                                <td><span class="badge text-bg-secondary">{{ $question->difficulty }}</span></td>
                                <td>{{ $question->score }}</td>
                                <td>{{ $question->hint ?? '-' }}</td>
                                <td>{{ $question->hint_2 ?? '-' }}</td>
                                <td><code>{{ $question->expected_command }}</code></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-secondary py-4">No questions found for this set.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
