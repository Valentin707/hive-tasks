@extends('layouts.guest')

@section('title', 'Sign in · HiveTasks')

@section('content')
    <div class="mb-6">
        <h1 class="text-xl font-semibold tracking-tight text-gray-900">Welcome back</h1>
        <p class="mt-1 text-sm text-gray-500">Sign in to continue to your workspace.</p>
    </div>

    <form method="POST" action="{{ route('login.store') }}">
        @csrf

        <div class="mb-6">
            <label for="email" class="form-label">Email address</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" required autofocus
                class="form-input" @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
            @error('email')
                <p id="email-error" class="form-error">{{ $message }}</p>
            @enderror
        </div>

        <div class="mb-6">
            <div class="mb-1.5 flex items-center justify-between">
                <label for="password" class="text-sm font-medium text-gray-700">Password</label>
            </div>
            <input id="password" name="password" type="password" autocomplete="current-password" required class="form-input"
                @error('password') aria-invalid="true" aria-describedby="password-error" @enderror>
            @error('password')
                <p id="password-error" class="form-error">{{ $message }}</p>
            @enderror
        </div>

        <label class="mb-6 flex items-center gap-2 text-sm text-gray-600">
            <input type="checkbox" name="remember" value="1" class="size-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
            Remember me
        </label>

        <button type="submit" class="button-primary mt-2 w-full">Sign in</button>
    </form>

    @if ($registrationEnabled)
        <p class="mt-6 text-center text-sm text-gray-600">
            New to HiveTasks?
            <a href="{{ route('register') }}" class="font-semibold text-indigo-600 transition hover:text-indigo-700">Create an account</a>
        </p>
    @endif
@endsection
