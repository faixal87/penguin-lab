@extends('layouts.app')

@section('title', ($set->exists ? 'Manage' : 'Create') . ' Question Set | ShellFix')
@section('page-title', $set->exists ? 'Manage Question Set' : 'Create Question Set')
@section('page-description', 'Question sets must total exactly 100 marks before activation.')

@section('content')
    @if (session('status'))<div class="alert alert-success border-0 shadow-sm">{{ session('status') }}</div>@endif
    @if (session('error'))<div class="alert alert-danger border-0 shadow-sm">{{ session('error') }}</div>@endif
    @if ($errors->any())<div class="alert alert-danger border-0 shadow-sm">{{ $errors->first() }}</div>@endif
    @php($total = $set->exists ? $set->currentTotal() : 0)
    @if($set->exists && $total !== 100)<div class="alert alert-warning border-0 shadow-sm">Current total is {{ $total }}/100. This set cannot be activated until it totals exactly 100 marks.</div>@endif
    @if($set->exists && ($assignedClasses ?? collect())->isNotEmpty())
        <div class="alert alert-warning border-0 shadow-sm">
            This set is assigned to class(es): <strong>{{ $assignedClasses->pluck('class_name')->join(', ') }}</strong>.
            It cannot be deleted until those assignments are removed.
        </div>
    @endif

    <div class="card border-0 shadow-sm mb-4"><div class="card-body">
        <form method="POST" action="{{ $set->exists ? route($routePrefix . '.question-sets.update', $set) : route($routePrefix . '.question-sets.store') }}" class="row g-3">
            @csrf @if($set->exists) @method('PUT') @endif
            <div class="col-md-5"><label class="form-label">Set Name</label><input class="form-control" name="name" value="{{ old('name', $set->name) }}" required><div class="form-text">Rename the question set here.</div></div>
            <div class="col-md-2"><label class="form-label">Target Marks</label><input type="number" class="form-control" name="total_marks" value="{{ old('total_marks', $set->total_marks ?: 100) }}" required></div>
            <div class="col-md-2"><label class="form-label">Status</label><select class="form-select" name="is_active"><option value="0" @selected(!old('is_active', $set->is_active))>Inactive</option><option value="1" @selected(old('is_active', $set->is_active))>Active</option></select></div>
            <div class="col-12"><label class="form-label">Description</label><textarea class="form-control" name="description" rows="2">{{ old('description', $set->description) }}</textarea></div>
            <div class="col-12"><button class="btn btn-primary">Save Set</button><a href="{{ route($routePrefix . '.question-sets.index') }}" class="btn btn-outline-secondary">Back</a></div>
        </form>
    </div></div>

    @if($set->exists)
        <div class="row g-4 mb-4">
            <div class="col-lg-6"><div class="card border-0 shadow-sm"><div class="card-body">
                <h2 class="h5">Add From Question Bank</h2>
                <form method="POST" action="{{ route($routePrefix . '.question-sets.questions.add', $set) }}" class="row g-2">@csrf
                    <div class="col-md-8"><select class="form-select" name="question_bank_id" required>@foreach($questions as $question)<option value="{{ $question->id }}">{{ $question->category }} - {{ $question->title }} {{ $question->created_by ? '(My question)' : '(Admin shared)' }}</option>@endforeach</select></div>
                    <div class="col-md-2"><input type="number" name="mark" class="form-control" placeholder="Mark"></div>
                    <div class="col-md-2"><button class="btn btn-outline-primary w-100">Add</button></div>
                </form>
            </div></div></div>
            <div class="col-lg-6"><div class="card border-0 shadow-sm"><div class="card-body">
                <h2 class="h5">Create Custom Question In Set</h2>
                <form method="POST" action="{{ route($routePrefix . '.question-sets.questions.custom', $set) }}" class="row g-2">@csrf
                    <div class="col-md-6"><input class="form-control" name="title" placeholder="Title" required></div>
                    <div class="col-md-3"><input class="form-control" name="category" placeholder="Category"></div>
                    <div class="col-md-3"><input class="form-control" name="difficulty" placeholder="Difficulty"></div>
                    <div class="col-md-2"><input type="number" class="form-control" name="score" value="10" min="1" max="100"></div>
                    <div class="col-md-5"><input class="form-control" name="description" placeholder="Description" required></div>
                    <div class="col-md-5"><input class="form-control" name="expected_answer" placeholder="Expected command" required></div>
                    <div class="col-12"><button class="btn btn-outline-primary">Create + Add</button></div>
                </form>
            </div></div></div>
        </div>
        <div class="card border-0 shadow-sm"><div class="card-body table-responsive">
            <h2 class="h5">Set Questions</h2>
            <table class="table align-middle mb-0"><thead><tr><th>Order</th><th>Question</th><th>Mark</th><th class="text-end">Actions</th></tr></thead><tbody>
                @forelse($set->setQuestions as $item)
                    <tr>
                        <td><form method="POST" action="{{ route($routePrefix . '.question-set-questions.update', $item) }}" class="d-flex gap-2">@csrf @method('PUT')<input type="number" name="sort_order" value="{{ $item->sort_order }}" class="form-control form-control-sm" style="width:80px"></td>
                        <td><div class="fw-semibold">{{ $item->question->title }}</div><div class="text-secondary small">{{ $item->question->category }} | {{ $item->question->difficulty }}</div></td>
                        <td><input type="number" name="mark" value="{{ $item->effectiveMark() }}" class="form-control form-control-sm" style="width:90px"></td>
                        <td class="text-end"><button class="btn btn-sm btn-outline-primary">Save</button></form><form method="POST" action="{{ route($routePrefix . '.question-set-questions.destroy', $item) }}" class="d-inline" onsubmit="return confirm('Remove question from set?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">Remove</button></form></td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center text-secondary py-4">No questions in this set.</td></tr>
                @endforelse
            </tbody></table>
        </div></div>

        <div class="card border-0 shadow-sm mt-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center gap-3 flex-wrap">
                    <div>
                        <h2 class="h5 mb-1">Delete Question Set</h2>
                        <p class="text-secondary mb-0">
                            @if(($assignedClasses ?? collect())->isNotEmpty())
                                Delete is locked because this set is assigned to one or more classes.
                            @else
                                This permanently deletes the set and its set-question links.
                            @endif
                        </p>
                    </div>
                    <form method="POST" action="{{ route($routePrefix . '.question-sets.destroy', $set) }}" onsubmit="return confirm('Delete this question set? This cannot be undone.')">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-outline-danger" @disabled(($assignedClasses ?? collect())->isNotEmpty())>Delete Set</button>
                    </form>
                </div>
            </div>
        </div>
    @endif
@endsection
