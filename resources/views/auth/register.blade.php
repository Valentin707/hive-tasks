@extends('layouts.guest')

@section('title', 'Create account · HiveTasks')

@section('content')
    <div class="mb-6">
        <h1 class="text-xl font-semibold tracking-tight text-gray-900">Create your account</h1>
        <p class="mt-1 text-sm text-gray-500">Start organizing your work in HiveTasks.</p>
    </div>

    <form method="POST" action="{{ route('register.store') }}">
        @csrf

        <div class="mb-6">
            <label for="name" class="form-label">Name</label>
            <input id="name" name="name" type="text" value="{{ old('name') }}" autocomplete="name" required autofocus class="form-input"
                @error('name') aria-invalid="true" aria-describedby="name-error" @enderror>
            @error('name')
                <p id="name-error" class="form-error">{{ $message }}</p>
            @enderror
        </div>

        <div class="mb-6">
            <label for="email" class="form-label">Email address</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" required class="form-input"
                @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
            @error('email')
                <p id="email-error" class="form-error">{{ $message }}</p>
            @enderror
        </div>

        <div class="mb-6">
            <label for="password" class="form-label">Password</label>
            <input id="password" name="password" type="password" autocomplete="new-password" required class="form-input"
                @error('password') aria-invalid="true" aria-describedby="password-error" @enderror>
            @error('password')
                <p id="password-error" class="form-error">{{ $message }}</p>
            @enderror
            <p class="mt-1.5 text-xs text-gray-500">Use at least 12 characters.</p>
        </div>

        <div class="mb-6">
            <label for="password_confirmation" class="form-label">Confirm password</label>
            <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required class="form-input">
        </div>

        <button type="submit" class="button-primary mt-2 w-full">Create account</button>
    </form>

    <p class="mt-6 text-center text-sm text-gray-600">
        Already have an account?
        <a href="{{ route('login') }}" class="font-semibold text-indigo-600 transition hover:text-indigo-700">Sign in</a>
    </p>
@endsection
