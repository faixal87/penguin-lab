@extends('layouts.app')

@section('title', 'Feedback Dashboard | ShellFix')
@section('page-title', 'Feedback Dashboard')
@section('page-description', auth()->user()->isLecturer() ? 'Feedback results from students in your classes.' : 'All course feedback results.')

@section('content')
    @if (session('status'))
        <div class="alert alert-success border-0 shadow-sm">{{ session('status') }}</div>
    @endif
    @if (session('warning'))
        <div class="alert alert-warning border-0 shadow-sm">{{ session('warning') }}</div>
    @endif

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('feedback.summary') }}" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label for="class_id" class="form-label">Class</label>
                    <select class="form-select" id="class_id" name="class_id">
                        <option value="">All classes</option>
                        @foreach ($filters['classes'] as $class)
                            <option value="{{ $class->id }}" @selected(($selected['class_id'] ?? '') == $class->id)>{{ $class->class_name }}</option>
                        @endforeach
                    </select>
                </div>
                @if (auth()->user()->isAdmin())
                    <div class="col-md-3">
                        <label for="lecturer_id" class="form-label">Lecturer</label>
                        <select class="form-select" id="lecturer_id" name="lecturer_id">
                            <option value="">All lecturers</option>
                            @foreach ($filters['lecturers'] as $lecturer)
                                <option value="{{ $lecturer->id }}" @selected(($selected['lecturer_id'] ?? '') == $lecturer->id)>{{ $lecturer->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="semester" class="form-label">Semester</label>
                        <select class="form-select" id="semester" name="semester">
                            <option value="">All</option>
                            @foreach ($filters['semesters'] as $semester)
                                <option value="{{ $semester }}" @selected(($selected['semester'] ?? '') === $semester)>{{ $semester }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div class="col-md-2">
                    <label for="category" class="form-label">Category</label>
                    <select class="form-select" id="category" name="category">
                        <option value="">All</option>
                        @foreach ($filters['categories'] as $category)
                            <option value="{{ $category }}" @selected(($selected['category'] ?? '') === $category)>{{ ucfirst($category) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center gap-3 flex-wrap">
                <div>
                    <h2 class="h5 mb-1">OpenAI Feedback Summary</h2>
                    <p class="text-secondary small mb-0">Generates from aggregated ratings only. Student names, emails, matric numbers, and personal data are not sent.</p>
                </div>
                <form method="POST" action="{{ route('feedback.summary.generate-ai') }}" class="mb-0">
                    @csrf
                    @foreach (['class_id', 'lecturer_id', 'semester', 'category'] as $filter)
                        @if (! empty($selected[$filter]))
                            <input type="hidden" name="{{ $filter }}" value="{{ $selected[$filter] }}">
                        @endif
                    @endforeach
                    <button type="submit" class="btn btn-primary">Generate AI Summary</button>
                </form>
            </div>

            @if ($latestAiSummary)
                <div class="mt-4 p-3 rounded border border-info">
                    <div class="d-flex justify-content-between gap-3 flex-wrap mb-2">
                        <div class="fw-semibold">Latest Generated Summary</div>
                        <div class="text-secondary small">
                            {{ $latestAiSummary->created_at?->format('Y-m-d H:i') }}
                            @if ($latestAiSummary->scope_type === 'class')
                                | {{ $latestAiSummary->schoolClass?->class_name ?? 'Selected class' }}
                            @else
                                | All filtered feedback
                            @endif
                        </div>
                    </div>
                    <div class="text-secondary ai-summary-output">{{ $latestAiSummary->summary }}</div>
                </div>
            @else
                <div class="text-secondary small mt-3">No generated AI summary has been stored for this selected scope yet.</div>
            @endif
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h2 class="h6 text-secondary">Total Respondents</h2>
                    <p class="display-6 fw-semibold mb-0">{{ $totalRespondents }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h2 class="h6 text-secondary">Overall Average Rating</h2>
                    <p class="display-6 fw-semibold mb-0">{{ $overallAverage ? number_format($overallAverage, 2) : '-' }} / 5</p>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <h2 class="h5">Average Rating by Category</h2>
            @forelse ($categoryAverages as $category)
                @php($percent = $category->average_rating ? ($category->average_rating / 5) * 100 : 0)
                <div class="mb-3">
                    <div class="d-flex justify-content-between">
                        <span class="text-capitalize">{{ $category->category }}</span>
                        <span>{{ $category->average_rating ? number_format($category->average_rating, 2) : '-' }} / 5 ({{ $category->response_count }})</span>
                    </div>
                    <div class="progress">
                        <div class="progress-bar" style="width: {{ $percent }}%"></div>
                    </div>
                </div>
            @empty
                <p class="text-secondary mb-0">No feedback submitted yet.</p>
            @endforelse
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h5">Trend by Class</h2>
                    @forelse ($classStats as $class)
                        @php($percent = $class->average_rating ? ($class->average_rating / 5) * 100 : 0)
                        <div class="mb-3">
                            <div class="d-flex justify-content-between">
                                <span>{{ $class->class_name }}</span>
                                <span>{{ number_format($class->average_rating, 2) }} / 5</span>
                            </div>
                            <div class="progress">
                                <div class="progress-bar bg-info" style="width: {{ $percent }}%"></div>
                            </div>
                        </div>
                    @empty
                        <p class="text-secondary mb-0">No class trend data yet.</p>
                    @endforelse
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h5">Response Count by Class</h2>
                    @php($maxResponses = max(1, $classStats->max('response_count') ?? 1))
                    @forelse ($classStats as $class)
                        @php($percent = ($class->response_count / $maxResponses) * 100)
                        <div class="mb-3">
                            <div class="d-flex justify-content-between">
                                <span>{{ $class->class_name }}</span>
                                <span>{{ $class->response_count }} respondents</span>
                            </div>
                            <div class="progress">
                                <div class="progress-bar bg-success" style="width: {{ $percent }}%"></div>
                            </div>
                        </div>
                    @empty
                        <p class="text-secondary mb-0">No response count data yet.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <h2 class="h5">Rating Distribution</h2>
            @php($maxRatingCount = max(1, $ratingDistribution->max('count') ?? 1))
            @foreach ($ratingDistribution as $item)
                @php($percent = ($item->count / $maxRatingCount) * 100)
                <div class="mb-3">
                    <div class="d-flex justify-content-between">
                        <span>{{ $item->rating }} star</span>
                        <span>{{ $item->count }} responses</span>
                    </div>
                    <div class="progress">
                        <div class="progress-bar bg-warning" style="width: {{ $percent }}%"></div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h5">Top 5 Highest Rated Questions</h2>
                    <ol class="mb-0">
                        @forelse ($topQuestions as $question)
                            <li class="mb-2">{{ $question->question_text }} <span class="badge text-bg-success">{{ number_format($question->average_rating, 2) }}</span></li>
                        @empty
                            <li class="text-secondary">No rated questions yet.</li>
                        @endforelse
                    </ol>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h5">Top 5 Lowest Rated Questions</h2>
                    <ol class="mb-0">
                        @forelse ($lowQuestions as $question)
                            <li class="mb-2">{{ $question->question_text }} <span class="badge text-bg-warning">{{ number_format($question->average_rating, 2) }}</span></li>
                        @empty
                            <li class="text-secondary">No rated questions yet.</li>
                        @endforelse
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <h2 class="h5">Rule-Based Summary Preview</h2>
            <div class="row g-3">
                <div class="col-md-6"><div class="p-3 rounded border border-secondary"><div class="fw-semibold mb-1">Strengths</div><p class="mb-0 text-secondary">{{ $aiSummary['strengths'] }}</p></div></div>
                <div class="col-md-6"><div class="p-3 rounded border border-secondary"><div class="fw-semibold mb-1">Weaknesses / Improvement Areas</div><p class="mb-0 text-secondary">{{ $aiSummary['weaknesses'] }}</p></div></div>
                <div class="col-md-6"><div class="p-3 rounded border border-secondary"><div class="fw-semibold mb-1">Learning Impact</div><p class="mb-0 text-secondary">{{ $aiSummary['learningImpact'] }}</p></div></div>
                <div class="col-md-6"><div class="p-3 rounded border border-secondary"><div class="fw-semibold mb-1">Student Engagement</div><p class="mb-0 text-secondary">{{ $aiSummary['engagement'] }}</p></div></div>
                <div class="col-md-6"><div class="p-3 rounded border border-secondary"><div class="fw-semibold mb-1">Class Comparison</div><p class="mb-0 text-secondary">{{ $aiSummary['classComparison'] }}</p></div></div>
                <div class="col-md-6"><div class="p-3 rounded border border-secondary"><div class="fw-semibold mb-1">Suggested CQI Actions</div><p class="mb-0 text-secondary">{{ $aiSummary['cqi'] }}</p></div></div>
                <div class="col-12"><div class="p-3 rounded border border-info"><div class="fw-semibold mb-1">Research Writing Paragraph Draft</div><p class="mb-0 text-secondary">{{ $aiSummary['paperParagraph'] }}</p></div></div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="h5 mb-0">Average Rating per Question</h2>
                <a href="{{ route('feedback.raw', request()->query()) }}" class="btn btn-sm btn-outline-primary">View Raw Data</a>
            </div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Category</th>
                            <th>Question</th>
                            <th>Average</th>
                            <th>Responses</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($questionAverages as $question)
                            <tr>
                                <td class="text-capitalize">{{ $question->category }}</td>
                                <td>{{ $question->question_text }}</td>
                                <td>{{ $question->average_rating ? number_format($question->average_rating, 2) : '-' }}</td>
                                <td>{{ $question->response_count }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-secondary py-4">No feedback submitted yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
