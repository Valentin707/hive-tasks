@extends('layouts.guest')

@section('title', 'Signed out · HiveTasks')

@section('content')
    <div class="text-center">
        <h1 class="text-xl font-semibold tracking-tight text-gray-900">You are signed out</h1>
        <p class="mt-2 text-sm text-gray-500">Your HiveTasks session has ended.</p>
        @if (session('auth-error'))
            <p role="status" class="mt-4 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900">{{ session('auth-error') }}</p>
        @endif
        <a href="{{ route('login') }}" class="button-primary mt-6 inline-flex w-full justify-center">Login with Hive Hub</a>
        <a href="{{ rtrim((string) config('services.hive.base_url'), '/') }}" class="mt-4 inline-block text-sm font-medium text-indigo-600 transition hover:text-indigo-700">Visit HIVE HUB</a>
    </div>
@endsection
