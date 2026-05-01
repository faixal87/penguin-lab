@extends('layouts.app')

@section('title', 'MY Penguin-LAB | Login')

@section('content')
    <main class="login-stage">
        <div class="login-grid">
            <section class="login-copy" aria-label="MY Penguin-LAB">
                <div class="login-kicker">Cyber Linux Training Arena</div>
                <h1 class="login-title">MY Penguin-LAB</h1>
                <p class="login-subtitle">Linux Learning Simulator</p>

                <div class="mascot-wrap" aria-hidden="true">
                    <div class="penguin-mascot">
                        <span class="penguin-eye left"></span>
                        <span class="penguin-eye right"></span>
                        <span class="penguin-beak"></span>
                        <span class="penguin-tag">JAY</span>
                        <span class="penguin-shirt">JTMK</span>
                        <span class="penguin-foot left"></span>
                        <span class="penguin-foot right"></span>
                    </div>
                </div>
            </section>

            <section class="login-card">
                <div class="mb-4">
                    <h2 class="h4 neon-title mb-1">Enter Lab</h2>
                    <p class="text-secondary mb-0">Authenticate to continue your shell mission.</p>
                </div>

                <form method="POST" action="{{ route('login.store') }}">
                    @csrf

                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email') }}" required autofocus>
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" required>
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-check mb-4">
                        <input class="form-check-input" type="checkbox" value="1" id="remember" name="remember">
                        <label class="form-check-label" for="remember">Remember me</label>
                    </div>

                    <button type="submit" class="btn btn-neon w-100">Launch Session</button>
                </form>
            </section>
        </div>
    </main>
@endsection
