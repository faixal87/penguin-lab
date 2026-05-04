@extends('layouts.app')

@section('title', ($question->exists ? 'Edit' : 'Create') . ' Question | ShellFix')
@section('page-title', $question->exists ? 'Edit Question' : 'Create Question')
@section('page-description', 'Question bank items can be reused across many sets.')

@section('content')
    @if ($errors->any())<div class="alert alert-danger border-0 shadow-sm">{{ $errors->first() }}</div>@endif
    <div class="card border-0 shadow-sm"><div class="card-body">
        <form method="POST" action="{{ $question->exists ? route($routePrefix . '.question-bank.update', $question) : route($routePrefix . '.question-bank.store') }}" class="row g-3">
            @csrf
            @if($question->exists) @method('PUT') @endif
            <div class="col-md-8"><label class="form-label">Title</label><input class="form-control" name="title" value="{{ old('title', $question->title) }}" required></div>
            <div class="col-md-4"><label class="form-label">Category/Topic</label><input class="form-control" name="category" value="{{ old('category', $question->category) }}"></div>
            <div class="col-md-6"><label class="form-label">Difficulty</label><input class="form-control" name="difficulty" value="{{ old('difficulty', $question->difficulty) }}"></div>
            <div class="col-md-3"><label class="form-label">Score</label><input type="number" min="1" max="100" class="form-control" name="score" value="{{ old('score', $question->score ?: 10) }}" required></div>
            <div class="col-md-3"><label class="form-label">Status</label><select class="form-select" name="is_active"><option value="1" @selected(old('is_active', $question->is_active) == 1)>Active</option><option value="0" @selected(old('is_active', $question->is_active) == 0)>Inactive</option></select></div>
            <div class="col-12"><label class="form-label">Description</label><textarea class="form-control" name="description" rows="4" required>{{ old('description', $question->description) }}</textarea></div>
            <div class="col-12"><label class="form-label">Expected Answer / Command</label><textarea class="form-control" name="expected_answer" rows="2" required>{{ old('expected_answer', $question->expected_answer) }}</textarea></div>
            <div class="col-md-6"><label class="form-label">Hint 1</label><textarea class="form-control" name="hint_1" rows="2">{{ old('hint_1', $question->hint_1) }}</textarea></div>
            <div class="col-md-6"><label class="form-label">Hint 2</label><textarea class="form-control" name="hint_2" rows="2">{{ old('hint_2', $question->hint_2) }}</textarea></div>
            <div class="col-12"><label class="form-label">Explanation</label><textarea class="form-control" name="explanation" rows="3">{{ old('explanation', $question->explanation) }}</textarea></div>
            <div class="col-12"><button class="btn btn-primary">Save</button><a href="{{ route($routePrefix . '.question-bank.index') }}" class="btn btn-outline-secondary">Back</a></div>
        </form>
    </div></div>
@endsection
