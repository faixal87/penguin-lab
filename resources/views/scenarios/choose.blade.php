@extends('layouts.app')

@section('title', 'Choose Question Set | ShellFix')
@section('page-title', 'Choose Question Set')
@section('page-description', 'You have more than one assigned set. Pick one to answer now.')

@section('content')
    <div class="row g-4">
        @foreach($sets as $set)
            @php($total = $set->currentTotal())
            <div class="col-md-6 col-xl-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex flex-column">
                        <div class="d-flex justify-content-between gap-2 mb-2">
                            <h2 class="h5 mb-0">{{ $set->name }}</h2>
                            <span class="badge text-bg-success">{{ $total }}/100</span>
                        </div>
                        <p class="text-secondary flex-grow-1">{{ $set->description ?: 'Assigned Linux command practice set.' }}</p>
                        <div class="small text-secondary mb-3">{{ $set->setQuestions->count() }} questions</div>
                        <form method="POST" action="{{ route('scenarios.choose.store') }}">
                            @csrf
                            <input type="hidden" name="question_set_id" value="{{ $set->id }}">
                            <button class="btn btn-primary w-100">Start This Set</button>
                        </form>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endsection
