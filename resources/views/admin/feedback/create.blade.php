@extends('layouts.app')

@section('title', 'Add Feedback Question | ShellFix')
@section('page-title', 'Add Feedback Question')
@section('page-description', 'Create a new course feedback question.')

@section('content')
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.feedback.store') }}">
                @include('admin.feedback._form', ['buttonText' => 'Create Question'])
            </form>
        </div>
    </div>
@endsection
