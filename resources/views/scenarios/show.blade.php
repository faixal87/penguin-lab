@extends('layouts.app')

@section('title', $scenario->title . ' | ShellFix')
@section('page-title', $scenario->title)
@section('page-description', 'Difficulty: ' . $scenario->difficulty . ' | Score: ' . $scenario->score)

@section('content')
    @php
        $answerResult = session('answer_result');
    @endphp

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <p class="lead">{{ $scenario->description }}</p>

            <div data-answer-result>
                @if ($answerResult)
                    <div class="alert {{ $answerResult['correct'] ? 'alert-success' : 'alert-danger' }}" role="alert">
                        <div class="fw-semibold">{{ $answerResult['correct'] ? 'Correct answer' : 'Wrong answer' }}</div>
                        <div>Hint used: {{ $answerResult['hint_used'] ? 'Yes' : 'No' }}</div>
                        <div>Mark awarded: {{ $answerResult['awarded_score'] }} / {{ $scenario->score }}</div>
                    </div>
                @endif

                @if ($errors->any())
                    <div class="alert alert-danger" data-answer-errors>
                        Please enter a command before submitting.
                    </div>
                @endif
            </div>

            <div class="mb-4">
                @if ($hintUsed)
                    <div class="alert alert-warning mb-0" role="alert">
                        <div class="fw-semibold mb-1">Hint</div>
                        {{ $scenario->hint ?? 'No hint is available for this question.' }}
                    </div>
                @else
                    <button type="button" class="btn btn-outline-warning" data-bs-toggle="modal" data-bs-target="#hintModal">
                        Show Hint
                    </button>
                @endif
            </div>

            <form method="POST" action="{{ route('scenarios.check', $scenario) }}" class="mt-4" data-answer-form>
                @csrf

                <div class="mb-3">
                    <label for="command" class="form-label">Your command</label>
                    <input
                        type="text"
                        class="form-control @error('command') is-invalid @enderror"
                        id="command"
                        name="command"
                        value="{{ old('command') }}"
                        placeholder="Enter the shell command"
                        autocomplete="off"
                    >
                    @error('command')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Submit</button>
                    <a href="{{ route('results') }}" class="btn btn-outline-primary">View Results</a>
                    <a href="{{ route('scenarios') }}" class="btn btn-outline-secondary">Back to scenarios</a>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="hintModal" tabindex="-1" aria-labelledby="hintModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title h5" id="hintModalLabel">Use Hint?</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Warning: Using hint will deduct 50% of the mark for this question. Continue?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form method="POST" action="{{ route('scenarios.hint', $scenario) }}">
                        @csrf
                        <button type="submit" class="btn btn-warning">Continue</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
