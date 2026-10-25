<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'HiveTasks')</title>
    @vite('resources/css/app.css')
</head>
<body class="min-h-screen bg-gray-50 font-sans text-gray-900 antialiased">
    <main class="flex min-h-screen items-center justify-center px-4 py-12 sm:px-6">
        <div class="w-full max-w-md">
            <a href="{{ route('login') }}" class="mb-8 flex items-center justify-center gap-3" aria-label="HiveTasks home">
                <span class="flex size-10 items-center justify-center rounded-xl bg-indigo-600 text-sm font-bold text-white shadow-sm">H</span>
                <span class="text-xl font-semibold tracking-tight text-gray-900">HiveTasks</span>
            </a>

            <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm sm:p-8">
                @yield('content')
            </section>

            <p class="mt-6 text-center text-xs text-gray-500">A calm space for your work.</p>
        </div>
    </main>
</body>
</html>
