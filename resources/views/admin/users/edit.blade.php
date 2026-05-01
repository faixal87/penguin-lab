@extends('layouts.app')

@section('title', 'Edit User | ShellFix')
@section('page-title', 'Edit User')
@section('page-description', 'Update user details, role, status, and password.')

@section('content')
    @if (session('status'))
        <div class="alert alert-success border-0 shadow-sm">{{ session('status') }}</div>
    @endif

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.users.update', $user) }}">
                @include('admin.users._form', ['buttonText' => 'Save Changes', 'method' => 'PUT'])
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm" id="reset-password">
        <div class="card-body">
            <h2 class="h5">Reset Password</h2>
            <form method="POST" action="{{ route('admin.users.password', $user) }}" class="row g-3">
                @csrf
                @method('PUT')
                <div class="col-md-5">
                    <label for="reset_password" class="form-label">New Password</label>
                    <input type="password" class="form-control @error('password') is-invalid @enderror" id="reset_password" name="password" required>
                    @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-5">
                    <label for="reset_password_confirmation" class="form-label">Confirm New Password</label>
                    <input type="password" class="form-control" id="reset_password_confirmation" name="password_confirmation" required>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-warning w-100">Reset</button>
                </div>
            </form>
        </div>
    </div>
@endsection
