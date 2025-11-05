@extends('layouts.guest')

@section('auth-title', 'Sign In to Your Account')

@section('content')
<form method="POST" action="{{ route('login') }}">
    @csrf

    <div class="mb-4">
        <label for="email" class="form-label fw-bold text-gray-700">Email Address</label>
        <div class="input-group">
            <span class="input-group-text"><i class="fas fa-envelope"></i></span>
            <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" 
                   name="email" value="{{ old('email') }}" 
                   placeholder="Enter your email" required autocomplete="email" autofocus>
        </div>
        @error('email')
            <div class="invalid-feedback d-block mt-2">
                <i class="fas fa-exclamation-circle me-1"></i>{{ $message }}
            </div>
        @enderror
    </div>

    <div class="mb-4">
        <label for="password" class="form-label fw-bold text-gray-700">Password</label>
        <div class="input-group">
            <span class="input-group-text"><i class="fas fa-lock"></i></span>
            <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" 
                   name="password" placeholder="Enter your password" required autocomplete="current-password">
        </div>
        @error('password')
            <div class="invalid-feedback d-block mt-2">
                <i class="fas fa-exclamation-circle me-1"></i>{{ $message }}
            </div>
        @enderror
    </div>

    <div class="mb-4">
        <div class="form-check">
            <input class="form-check-input" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
            <label class="form-check-label text-gray-700" for="remember">
                Remember Me
            </label>
        </div>
    </div>

    <div class="d-grid mb-4">
        <button type="submit" class="btn btn-primary btn-lg py-3 fw-bold">
            <i class="fas fa-sign-in-alt me-2"></i>{{ __('Sign In') }}
        </button>
    </div>

    <div class="text-center">
        <p class="text-gray-600 mb-0">
            Don't have an account? 
            <a href="{{ route('register') }}" class="text-gradient text-decoration-none fw-bold">Create Account</a>
        </p>
    </div>
</form>
@endsection