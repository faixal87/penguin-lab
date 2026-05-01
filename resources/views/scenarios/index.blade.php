@extends('layouts.app')

@section('title', 'Scenarios | ShellFix')
@section('page-title', 'Scenarios')
@section('page-description', 'Practice shell commands through guided troubleshooting scenarios.')

@section('content')
    <div class="alert alert-info border-0 shadow-sm" role="alert">
        You are assigned Set {{ $examSet }}.
    </div>

    <div class="card border-0 shadow-sm scenario-panel" data-scenarios-panel data-load-url="{{ route('scenarios') }}">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th scope="col">Title</th>
                            <th scope="col">Difficulty</th>
                            <th scope="col">Score</th>
                            <th scope="col">Your Score</th>
                            <th scope="col">Status</th>
                            <th scope="col" class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($scenarios as $scenario)
                            @php
                                $answer = $answersByScenario->get($scenario->id);
                                $awardedScore = $answer ? rtrim(rtrim(number_format((float) $answer->score_awarded, 2, '.', ''), '0'), '.') : null;
                            @endphp
                            <tr>
                                <td class="fw-semibold">{{ $scenario->title }}</td>
                                <td>
                                    <span class="badge text-bg-primary">{{ $scenario->difficulty }}</span>
                                </td>
                                <td>{{ $scenario->score }}</td>
                                <td>
                                    @if ($answer)
                                        {{ $awardedScore }}/{{ $scenario->score }}
                                    @else
                                        -/{{ $scenario->score }}
                                        <span class="text-secondary small">Not attempted</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($answer)
                                        <span class="status-glow-success fw-semibold">&#10003; Answered</span>
                                    @else
                                        <span class="status-glow-danger fw-semibold">&#10005; Not answered</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <a class="btn btn-sm btn-outline-primary" href="{{ route('scenarios.show', $scenario) }}">Open</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-secondary py-4">No scenarios have been added yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="3" class="text-end">Total Score</th>
                            <th colspan="3">{{ rtrim(rtrim(number_format((float) $totalAwarded, 2, '.', ''), '0'), '.') }}/{{ $totalPossible }}</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
@endsection
