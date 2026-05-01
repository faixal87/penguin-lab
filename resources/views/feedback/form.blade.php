@extends('layouts.app')

@section('title', 'Course Feedback | ShellFix')
@section('page-title', 'Course Feedback')
@section('page-description', 'Rate each statement from 1 strongly disagree to 5 strongly agree.')

@section('content')
    <form method="POST" action="{{ route('feedback.submit') }}">
        @csrf

        @foreach ($questions as $category => $items)
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <h2 class="h5 text-capitalize">{{ $category }}</h2>

                    @foreach ($items as $question)
                        <div class="border-top pt-3 mt-3">
                            <div class="fw-semibold mb-2">{{ $question->question_text }}</div>
                            <div class="d-flex flex-wrap gap-3">
                                @for ($rating = 1; $rating <= 5; $rating++)
                                    <div class="form-check">
                                        <input
                                            class="form-check-input @error('ratings.' . $question->id) is-invalid @enderror"
                                            type="radio"
                                            name="ratings[{{ $question->id }}]"
                                            id="question_{{ $question->id }}_{{ $rating }}"
                                            value="{{ $rating }}"
                                            @checked(old('ratings.' . $question->id) == $rating)
                                            required
                                        >
                                        <label class="form-check-label" for="question_{{ $question->id }}_{{ $rating }}">{{ $rating }}</label>
                                    </div>
                                @endfor
                            </div>
                            @error('ratings.' . $question->id)
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach

        <button type="submit" class="btn btn-primary">Submit Feedback</button>
        <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary">Back</a>
    </form>
@endsection
