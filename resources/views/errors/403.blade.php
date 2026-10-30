<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>403 Unauthorized · HiveTasks</title>
    @vite('resources/css/app.css')
</head>
<body class="min-h-screen bg-gray-50 font-sans text-gray-900 antialiased dark:bg-gray-950 dark:text-gray-100">
    <main class="flex min-h-screen items-center justify-center px-4 py-12 sm:px-6">
        <section class="w-full max-w-lg rounded-2xl border border-gray-200 bg-white p-7 text-center shadow-xl shadow-gray-950/10 sm:p-9 dark:border-gray-200 dark:bg-gray-100 dark:shadow-black/30" aria-labelledby="unauthorized-title">
            <span class="mx-auto flex size-12 items-center justify-center rounded-xl bg-red-50 text-red-600 dark:bg-red-950/60 dark:text-red-300">
                <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <circle cx="12" cy="12" r="8.25" />
                    <path stroke-linecap="round" d="m9 9 6 6m0-6-6 6" />
                </svg>
            </span>
            <p class="mt-5 font-mono text-sm font-semibold tracking-wider text-red-600 dark:text-red-400">403 · UNAUTHORIZED</p>
            <h1 id="unauthorized-title" class="mt-2 text-2xl font-semibold tracking-tight text-gray-900">Access denied</h1>
            <p class="mx-auto mt-3 max-w-sm text-sm leading-6 text-gray-500">Your account does not have permission to access this area.</p>
            <a href="{{ auth()->check() ? route('dashboard') : route('login') }}" class="button-primary mt-7 w-full sm:w-auto">
                {{ auth()->check() ? 'Return to dashboard' : 'Return to sign in' }}
            </a>
        </section>
    </main>
</body>
</html>
