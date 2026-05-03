@csrf

@if (isset($method))
    @method($method)
@endif

<div class="row g-3">
    <div class="col-md-6">
        <label for="name" class="form-label">Name</label>
        <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $user->name ?? '') }}" required>
        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label for="email" class="form-label">Email</label>
        <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email', $user->email ?? '') }}" required>
        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label for="role" class="form-label">Role</label>
        <select class="form-select @error('role') is-invalid @enderror" id="role" name="role" required>
            @foreach (['admin', 'lecturer', 'student'] as $option)
                <option value="{{ $option }}" @selected(old('role', $user->role ?? $role ?? 'student') === $option)>{{ ucfirst($option) }}</option>
            @endforeach
        </select>
        @error('role')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label for="status" class="form-label">Status</label>
        <select class="form-select @error('status') is-invalid @enderror" id="status" name="status" required>
            @foreach (['approved', 'pending', 'suspended'] as $option)
                <option value="{{ $option }}" @selected(old('status', $user->status ?? 'approved') === $option)>{{ ucfirst($option) }}</option>
            @endforeach
        </select>
        @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label for="phone_no" class="form-label">Phone No</label>
        <input type="text" class="form-control @error('phone_no') is-invalid @enderror" id="phone_no" name="phone_no" value="{{ old('phone_no', $user->phone_no ?? '') }}">
        @error('phone_no')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label for="matric_no" class="form-label">Matric No</label>
        <input type="text" class="form-control @error('matric_no') is-invalid @enderror" id="matric_no" name="matric_no" value="{{ old('matric_no', $user->matric_no ?? '') }}">
        @error('matric_no')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label for="registration_no" class="form-label">Registration No</label>
        <input type="text" class="form-control @error('registration_no') is-invalid @enderror" id="registration_no" name="registration_no" value="{{ old('registration_no', $user->registration_no ?? '') }}">
        @error('registration_no')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label for="staff_no" class="form-label">Staff No</label>
        <input type="text" class="form-control @error('staff_no') is-invalid @enderror" id="staff_no" name="staff_no" value="{{ old('staff_no', $user->staff_no ?? '') }}">
        @error('staff_no')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label for="program" class="form-label">Program</label>
        <input type="text" class="form-control @error('program') is-invalid @enderror" id="program" name="program" value="{{ old('program', $user->program ?? '') }}">
        @error('program')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label for="semester" class="form-label">Semester</label>
        <input type="text" class="form-control @error('semester') is-invalid @enderror" id="semester" name="semester" value="{{ old('semester', $user->semester ?? '') }}">
        @error('semester')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label for="class_name" class="form-label">Class Name</label>
        <input type="text" class="form-control @error('class_name') is-invalid @enderror" id="class_name" name="class_name" value="{{ old('class_name', $user->class_name ?? '') }}">
        @error('class_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label for="department" class="form-label">Department</label>
        <input type="text" class="form-control @error('department') is-invalid @enderror" id="department" name="department" value="{{ old('department', $user->department ?? '') }}">
        @error('department')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label for="password" class="form-label">Password</label>
        <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" @if (! isset($user)) required @endif>
        @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label for="password_confirmation" class="form-label">Confirm Password</label>
        <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" @if (! isset($user)) required @endif>
    </div>

    <div class="col-12">
        <div class="card border-0 shadow-sm mt-2">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                    <div>
                        <h2 class="h5 mb-1">Terminal Access</h2>
                        <p class="text-secondary mb-0">Phase T2 only stores access details. Docker and Guacamole are not connected yet.</p>
                    </div>
                    <div class="form-check form-switch">
                        <input type="hidden" name="terminal_enabled" value="0">
                        <input class="form-check-input" type="checkbox" role="switch" id="terminal_enabled" name="terminal_enabled" value="1" @checked(old('terminal_enabled', $user->terminal_enabled ?? false))>
                        <label class="form-check-label" for="terminal_enabled">Enabled</label>
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="linux_username" class="form-label">Linux Username</label>
                        <input type="text" class="form-control @error('linux_username') is-invalid @enderror" id="linux_username" name="linux_username" value="{{ old('linux_username', $user->linux_username ?? '') }}">
                        @error('linux_username')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label for="container_name" class="form-label">Container Name</label>
                        <input type="text" class="form-control @error('container_name') is-invalid @enderror" id="container_name" name="container_name" value="{{ old('container_name', $user->container_name ?? '') }}">
                        @error('container_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12">
                        <div class="alert alert-warning mb-0">Do not store Linux passwords in ShellFix. Students will use credentials assigned outside this system.</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="mt-4 d-flex gap-2">
    <button type="submit" class="btn btn-primary">{{ $buttonText }}</button>
    <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">Cancel</a>
</div>
