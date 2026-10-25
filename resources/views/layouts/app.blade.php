<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', config('app.name', 'HiveTasks'))</title>

    @livewireStyles
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gray-50 font-sans text-gray-900 antialiased">
    <div class="min-h-screen lg:flex">
        <button
            type="button"
            class="fixed inset-0 z-30 hidden bg-gray-950/40 backdrop-blur-[1px] lg:hidden"
            aria-label="Close navigation menu"
            data-sidebar-overlay
        ></button>

        <aside
            id="primary-navigation"
            class="fixed inset-y-0 left-0 z-40 flex w-72 -translate-x-full flex-col border-r border-gray-200 bg-white transition-transform duration-200 ease-out lg:static lg:z-auto lg:translate-x-0"
            data-mobile-sidebar
        >
            <div class="flex h-16 shrink-0 items-center gap-3 border-b border-gray-100 px-6">
                <div class="flex size-9 items-center justify-center rounded-xl bg-indigo-600 text-sm font-bold text-white shadow-sm">
                    H
                </div>
                <span class="text-lg font-semibold tracking-tight text-gray-900">HiveTasks</span>
            </div>

            <nav class="flex-1 space-y-1 px-3 py-6" aria-label="Main navigation">
                <a
                    href="{{ route('projects.index') }}"
                    class="flex items-center gap-3 rounded-lg bg-indigo-50 px-3 py-2.5 text-sm font-semibold text-indigo-700 transition hover:bg-indigo-100"
                >
                    <svg class="size-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <rect x="3.75" y="4.75" width="7" height="6.5" rx="1.5" />
                        <rect x="13.25" y="4.75" width="7" height="14.5" rx="1.5" />
                        <rect x="3.75" y="14.25" width="7" height="5" rx="1.5" />
                    </svg>
                    <span>Projects</span>
                </a>

                <a
                    href="{{ url('/notes') }}"
                    class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-gray-600 transition hover:bg-gray-100 hover:text-gray-900"
                >
                    <svg class="size-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M7 3.75h7l5 5v11.5H7a2 2 0 0 1-2-2v-12.5a2 2 0 0 1 2-2Z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14 3.75v5h5M8.5 13h7M8.5 16.5h7" />
                    </svg>
                    <span>Notes</span>
                </a>
            </nav>

            <div class="border-t border-gray-100 p-4">
                <div class="flex items-center gap-3 rounded-xl bg-gray-50 p-3">
                    <div class="flex size-9 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-xs font-semibold text-indigo-700" aria-hidden="true">
                        HT
                    </div>
                    <div class="min-w-0">
                        <p class="truncate text-sm font-medium text-gray-900">HiveTasks Workspace</p>
                        <p class="truncate text-xs text-gray-500">Personal Kanban</p>
                    </div>
                </div>
            </div>
        </aside>

        <div class="flex min-h-screen min-w-0 flex-1 flex-col">
            <header class="sticky top-0 z-20 flex h-16 shrink-0 items-center justify-between border-b border-gray-200 bg-white/95 px-4 backdrop-blur sm:px-6 lg:px-8">
                <div class="flex min-w-0 items-center gap-3">
                    <button
                        type="button"
                        class="-ml-2 inline-flex size-10 items-center justify-center rounded-lg text-gray-600 transition hover:bg-gray-100 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 lg:hidden"
                        aria-label="Open navigation menu"
                        aria-controls="primary-navigation"
                        aria-expanded="false"
                        data-sidebar-toggle
                    >
                        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path stroke-linecap="round" d="M4 6.75h16M4 12h16M4 17.25h16" />
                        </svg>
                    </button>
                    <div class="min-w-0">
                        <p class="truncate text-sm font-semibold text-gray-900">My First Project</p>
                        <p class="hidden text-xs text-gray-500 sm:block">Workspace</p>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <span class="hidden text-sm text-gray-500 sm:inline">Personal workspace</span>
                    <div class="flex size-9 items-center justify-center rounded-full bg-gray-100 text-xs font-semibold text-gray-700 ring-1 ring-inset ring-gray-200" aria-label="Profile placeholder">
                        HT
                    </div>
                </div>
            </header>

            <main class="flex-1 bg-gray-50 px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
                <div class="mx-auto w-full max-w-7xl">
                    @yield('content')
                </div>
            </main>
        </div>
    </div>
    @livewireScripts
</body>
</html>
