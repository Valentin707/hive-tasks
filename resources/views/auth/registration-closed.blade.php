<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Registration closed · HiveTasks</title>
    @vite('resources/css/app.css')
</head>
<body class="min-h-screen bg-gray-50 font-sans text-gray-900 antialiased dark:bg-gray-950 dark:text-gray-100">
    <main class="flex min-h-screen items-center justify-center px-4 py-12 sm:px-6">
        <div class="w-full max-w-lg">
            <a href="{{ route('login') }}" class="mb-8 flex items-center justify-center gap-3" aria-label="HiveTasks sign in">
                <span class="flex size-10 items-center justify-center rounded-xl bg-indigo-600 text-sm font-bold text-white shadow-sm">H</span>
                <span class="text-xl font-semibold tracking-tight text-gray-900">HiveTasks</span>
            </a>

            <section class="rounded-2xl border border-gray-200 bg-white p-7 text-center shadow-xl shadow-gray-950/10 sm:p-9 dark:border-gray-200 dark:bg-gray-100 dark:shadow-black/30" aria-labelledby="registration-closed-title">
                <span class="mx-auto flex size-12 items-center justify-center rounded-xl bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300">
                    <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <rect x="5" y="10" width="14" height="10" rx="2" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 10V7a4 4 0 1 1 8 0v3m-4 4v2" />
                    </svg>
                </span>
                <p class="mt-5 text-sm font-semibold text-indigo-600 dark:text-indigo-400">HiveTasks registration</p>
                <h1 id="registration-closed-title" class="mt-2 text-2xl font-semibold tracking-tight text-gray-900">Public registration is closed</h1>
                <p class="mx-auto mt-3 max-w-sm text-sm leading-6 text-gray-500">New account sign-ups are currently disabled by the workspace administrator. You can still sign in if you already have an account.</p>
                <a href="{{ route('login') }}" class="button-primary mt-7 w-full sm:w-auto">Return to sign in</a>
            </section>

            <p class="mt-6 text-center text-xs text-gray-500">A calm space for your work.</p>
        </div>
    </main>
</body>
</html>
