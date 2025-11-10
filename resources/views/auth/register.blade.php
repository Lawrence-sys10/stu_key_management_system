@extends('layouts.guest')

@section('auth-title', 'Create Your Account')

@section('content')
<form method="POST" action="{{ route('register') }}">
    @csrf

    <div class="mb-4">
        <label for="name" class="form-label fw-bold text-gray-700">Full Name</label>
        <div class="input-group">
            <span class="input-group-text"><i class="fas fa-user"></i></span>
            <input id="name" type="text" class="form-control @error('name') is-invalid @enderror" 
                   name="name" value="{{ old('name') }}" 
                   placeholder="Enter your full name" required autocomplete="name" autofocus>
        </div>
        @error('name')
            <div class="invalid-feedback d-block mt-2">
                <i class="fas fa-exclamation-circle me-1"></i>{{ $message }}
            </div>
        @enderror
    </div>

    <div class="mb-4">
        <label for="email" class="form-label fw-bold text-gray-700">Email Address</label>
        <div class="input-group">
            <span class="input-group-text"><i class="fas fa-envelope"></i></span>
            <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" 
                   name="email" value="{{ old('email') }}" 
                   placeholder="Enter your email" required autocomplete="email">
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
                   name="password" placeholder="Create a password" required autocomplete="new-password">
        </div>
        @error('password')
            <div class="invalid-feedback d-block mt-2">
                <i class="fas fa-exclamation-circle me-1"></i>{{ $message }}
            </div>
        @enderror
    </div>

    <div class="mb-4">
        <label for="password-confirm" class="form-label fw-bold text-gray-700">Confirm Password</label>
        <div class="input-group">
            <span class="input-group-text"><i class="fas fa-lock"></i></span>
            <input id="password-confirm" type="password" class="form-control" 
                   name="password_confirmation" placeholder="Confirm your password" required autocomplete="new-password">
        </div>
    </div>

    <div class="d-grid mb-4">
        <button type="submit" class="btn btn-primary btn-lg py-3 fw-bold">
            <i class="fas fa-user-plus me-2"></i>{{ __('Create Account') }}
        </button>
    </div>

    <div class="text-center">
        <p class="text-gray-600 mb-0">
            Already have an account? 
            <a href="{{ route('login') }}" class="text-gradient text-decoration-none fw-bold">Sign In</a>
        </p>
    </div>
</form>
@endsection