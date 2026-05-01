@csrf

@if (isset($method))
    @method($method)
@endif

<div class="mb-3">
    <label for="question_text" class="form-label">Question Text</label>
    <textarea class="form-control @error('question_text') is-invalid @enderror" id="question_text" name="question_text" rows="3" required>{{ old('question_text', $question->question_text ?? '') }}</textarea>
    @error('question_text')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>

<div class="mb-3">
    <label for="category" class="form-label">Category</label>
    <select class="form-select @error('category') is-invalid @enderror" id="category" name="category" required>
        @foreach (['usability', 'learning effectiveness', 'engagement', 'assessment'] as $category)
            <option value="{{ $category }}" @selected(old('category', $question->category ?? '') === $category)>{{ ucfirst($category) }}</option>
        @endforeach
    </select>
    @error('category')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>

<div class="form-check mb-4">
    <input type="hidden" name="is_active" value="0">
    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" @checked(old('is_active', $question->is_active ?? true))>
    <label class="form-check-label" for="is_active">Active</label>
</div>

<button type="submit" class="btn btn-primary">{{ $buttonText }}</button>
<a href="{{ route('admin.feedback.index') }}" class="btn btn-outline-secondary">Cancel</a>
