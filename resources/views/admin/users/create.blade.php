@extends('layouts.app')

@section('title', 'Add ' . ucfirst($role) . ' | ShellFix')
@section('page-title', 'Add ' . ucfirst($role))
@section('page-description', 'Create a new ' . $role . ' account.')

@section('content')
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.users.store') }}">
                @include('admin.users._form', ['buttonText' => 'Create User'])
            </form>
        </div>
    </div>
@endsection
