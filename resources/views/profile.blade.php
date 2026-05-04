@extends('layouts.app')

@section('title', 'Profile | ShellFix')
@section('page-title', 'Profile')
@section('page-description', 'Update your ShellFix account details.')

@section('content')
    @if (session('status'))
        <div class="alert alert-success border-0 shadow-sm">{{ session('status') }}</div>
    @endif

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <img src="{{ $user->profilePhotoUrl() }}" alt="{{ $user->name }}" class="rounded-circle mb-3" style="width: 120px; height: 120px; object-fit: cover;">
                    <h2 class="h5 mb-1">{{ $user->name }}</h2>
                    <p class="text-secondary mb-2">{{ ucfirst($user->role) }}</p>
                    @if ($user->isStudent())
                        <span class="badge text-bg-primary badge-glow">{{ $badge }}</span>
                        <div class="text-secondary small mt-2">Total score: {{ $totalScore }}</div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="name" class="form-label">Name</label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $user->name) }}" required>
                                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email', $user->email) }}" required>
                                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="profile_photo" class="form-label">Profile Photo</label>
                                <input type="file" class="form-control @error('profile_photo') is-invalid @enderror" id="profile_photo" name="profile_photo" accept="image/*">
                                @error('profile_photo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label">Default Avatar</label>
                                <div class="d-flex gap-3 flex-wrap">
                                    @foreach(\App\Models\User::defaultAvatars() as $avatar => $label)
                                        <label class="text-center">
                                            <input type="radio" class="form-check-input me-1" name="default_avatar" value="{{ $avatar }}" @checked(old('default_avatar', $user->default_avatar ?: 'penguin-1.svg') === $avatar)>
                                            <img src="{{ asset('assets/avatars/' . $avatar) }}" alt="{{ $label }}" class="rounded-circle d-block my-1" style="width:64px;height:64px;object-fit:cover;">
                                            <span class="small">{{ $label }}</span>
                                        </label>
                                    @endforeach
                                </div>
                                @error('default_avatar')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="phone_no" class="form-label">Phone No</label>
                                <input type="text" class="form-control @error('phone_no') is-invalid @enderror" id="phone_no" name="phone_no" value="{{ old('phone_no', $user->phone_no) }}">
                                @error('phone_no')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label for="program" class="form-label">Program</label>
                                <input type="text" class="form-control @error('program') is-invalid @enderror" id="program" name="program" value="{{ old('program', $user->program) }}">
                                @error('program')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label for="semester" class="form-label">Semester</label>
                                <input type="text" class="form-control @error('semester') is-invalid @enderror" id="semester" name="semester" value="{{ old('semester', $user->semester) }}">
                                @error('semester')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label for="class_name" class="form-label">Class Name</label>
                                <input type="text" class="form-control @error('class_name') is-invalid @enderror" id="class_name" name="class_name" value="{{ old('class_name', $user->class_name) }}">
                                @error('class_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            @if ($user->isStudent())
                                <div class="col-md-6">
                                    <label for="matric_no" class="form-label">Matric No</label>
                                    <input type="text" class="form-control @error('matric_no') is-invalid @enderror" id="matric_no" name="matric_no" value="{{ old('matric_no', $user->matric_no) }}">
                                    @error('matric_no')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-6">
                                    <label for="registration_no" class="form-label">Registration No</label>
                                    <input type="text" class="form-control @error('registration_no') is-invalid @enderror" id="registration_no" name="registration_no" value="{{ old('registration_no', $user->registration_no) }}">
                                    @error('registration_no')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            @endif

                            @if ($user->isLecturer())
                                <div class="col-md-6">
                                    <label for="staff_no" class="form-label">Staff No</label>
                                    <input type="text" class="form-control @error('staff_no') is-invalid @enderror" id="staff_no" name="staff_no" value="{{ old('staff_no', $user->staff_no) }}">
                                    @error('staff_no')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-6">
                                    <label for="department" class="form-label">Department</label>
                                    <input type="text" class="form-control @error('department') is-invalid @enderror" id="department" name="department" value="{{ old('department', $user->department) }}">
                                    @error('department')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            @endif
                        </div>

                        <button type="submit" class="btn btn-primary mt-4">Update Profile</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
