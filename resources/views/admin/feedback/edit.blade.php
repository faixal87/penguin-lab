@extends('layouts.app')

@section('title', 'Edit Feedback Question | ShellFix')
@section('page-title', 'Edit Feedback Question')
@section('page-description', 'Update a course feedback question.')

@section('content')
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.feedback.update', $feedbackQuestion) }}">
                @include('admin.feedback._form', ['buttonText' => 'Save Changes', 'method' => 'PUT', 'question' => $feedbackQuestion])
            </form>
        </div>
    </div>
@endsection
