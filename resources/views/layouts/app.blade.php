<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" @class(['dark' => auth()->user()?->theme_preference === 'dark'])>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', config('app.name', 'HiveTasks'))</title>

    @livewireStyles
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gray-50 font-sans text-gray-900 antialiased">
    @php
        $userNameParts = preg_split('/\s+/', trim(auth()->user()->name), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $userInitials = mb_strtoupper(mb_substr($userNameParts[0] ?? '', 0, 1).mb_substr($userNameParts[1] ?? '', 0, 1));
        $pageTitle = trim(strip_tags($__env->yieldContent('title', config('app.name', 'HiveTasks'))));
        $pageSubtitle = match (true) {
            request()->routeIs('projects.board') => 'Kanban board',
            request()->routeIs('projects.index', 'dashboard') => 'Project workspace',
            request()->routeIs('notes.index') => 'Notes workspace',
            request()->routeIs('profile.index') => 'Account settings',
            default => 'Workspace',
        };
    @endphp

    <div
        x-data="{
            sidebarOpen: window.matchMedia('(min-width: 1024px)').matches && (() => {
                try {
                    return window.localStorage.getItem('hivetasks.sidebarOpen') !== 'closed';
                } catch {
                    return true;
                }
            })(),
            desktopViewport: window.matchMedia('(min-width: 1024px)').matches,
            readSidebarPreference() {
                try {
                    return window.localStorage.getItem('hivetasks.sidebarOpen') !== 'closed';
                } catch {
                    return true;
                }
            },
            toggleSidebar() {
                this.sidebarOpen = !this.sidebarOpen;

                if (this.desktopViewport) {
                    try {
                        window.localStorage.setItem('hivetasks.sidebarOpen', this.sidebarOpen ? 'open' : 'closed');
                    } catch {
                        // Keep the toggle usable when browser storage is unavailable.
                    }
                }
            },
            syncViewport() {
                const isDesktop = window.matchMedia('(min-width: 1024px)').matches;

                if (isDesktop !== this.desktopViewport) {
                    this.desktopViewport = isDesktop;
                    this.sidebarOpen = isDesktop ? this.readSidebarPreference() : false;
                }
            },
        }"
        x-on:resize.window.debounce.100ms="syncViewport()"
        x-on:keydown.escape.window="if (!desktopViewport) { sidebarOpen = false; $nextTick(() => $refs.sidebarToggle.focus()) }"
        class="min-h-screen lg:flex"
    >
        <button
            type="button"
            x-cloak
            x-show="sidebarOpen && !desktopViewport"
            x-transition.opacity
            @click="sidebarOpen = false; $nextTick(() => $refs.sidebarToggle.focus())"
            class="fixed inset-0 z-30 bg-gray-950/40 backdrop-blur-[1px]"
            aria-label="Close navigation menu"
        ></button>

        <aside
            id="primary-navigation"
            x-cloak
            class="fixed inset-y-0 left-0 z-40 flex shrink-0 flex-col overflow-hidden border-r border-gray-200 bg-white shadow-xl transition-all duration-300 ease-in-out lg:static lg:inset-y-auto lg:left-auto lg:z-auto lg:shadow-none"
            :class="sidebarOpen ? 'w-64 translate-x-0' : 'w-0 -translate-x-full lg:w-16 lg:translate-x-0'"
            :inert="!sidebarOpen && !desktopViewport"
            :aria-hidden="(!sidebarOpen && !desktopViewport).toString()"
        >
            <div class="flex h-16 shrink-0 items-center gap-3 border-b border-gray-100 px-3">
                <div class="flex min-w-0 items-center gap-3">
                    <div class="flex size-9 items-center justify-center rounded-xl bg-indigo-600 text-sm font-bold text-white shadow-sm">
                        H
                    </div>
                    <span x-cloak x-show="sidebarOpen" x-transition.opacity class="truncate text-lg font-semibold tracking-tight text-gray-900">HiveTasks</span>
                </div>
            </div>

            <div class="shrink-0 border-b border-gray-100 p-2" :class="sidebarOpen ? 'lg:p-4' : 'lg:p-2'">
                <div class="flex items-center gap-3 rounded-xl bg-gray-50 p-3" :class="sidebarOpen ? '' : 'lg:justify-center lg:bg-transparent lg:p-0'">
                    <div class="flex size-9 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-xs font-semibold text-indigo-700" aria-hidden="true">
                        {{ $userInitials }}
                    </div>
                    <div x-cloak x-show="sidebarOpen" x-transition.opacity class="min-w-0">
                        <p class="truncate text-sm font-medium text-gray-900">{{ auth()->user()->name }}</p>
                        <p class="truncate text-xs text-gray-500">{{ auth()->user()->email }}</p>
                    </div>
                </div>
            </div>

            <nav class="flex-1 space-y-1 px-2 py-6" aria-label="Main navigation">
                <a
                    href="{{ route('projects.index') }}"
                    title="Projects"
                    aria-label="Projects"
                    x-on:click="if (!desktopViewport) sidebarOpen = false"
                    :class="sidebarOpen ? 'justify-start px-3' : 'justify-center px-2'"
                    @class([
                        'flex items-center gap-3 rounded-lg py-2.5 text-sm transition',
                        'bg-indigo-50 font-semibold text-indigo-700 hover:bg-indigo-100' => request()->routeIs('dashboard', 'projects.*'),
                        'font-medium text-gray-600 hover:bg-gray-100 hover:text-gray-900' => ! request()->routeIs('dashboard', 'projects.*'),
                    ])
                >
                    <svg class="size-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <rect x="3.75" y="4.75" width="7" height="6.5" rx="1.5" />
                        <rect x="13.25" y="4.75" width="7" height="14.5" rx="1.5" />
                        <rect x="3.75" y="14.25" width="7" height="5" rx="1.5" />
                    </svg>
                    <span x-cloak x-show="sidebarOpen" x-transition.opacity>Projects</span>
                </a>

                <a
                    href="{{ route('notes.index') }}"
                    title="Notes"
                    aria-label="Notes"
                    x-on:click="if (!desktopViewport) sidebarOpen = false"
                    :class="sidebarOpen ? 'justify-start px-3' : 'justify-center px-2'"
                    @class([
                        'flex items-center gap-3 rounded-lg py-2.5 text-sm transition',
                        'bg-indigo-50 font-semibold text-indigo-700 hover:bg-indigo-100' => request()->routeIs('notes.*'),
                        'font-medium text-gray-600 hover:bg-gray-100 hover:text-gray-900' => ! request()->routeIs('notes.*'),
                    ])
                >
                    <svg class="size-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M7 3.75h7l5 5v11.5H7a2 2 0 0 1-2-2v-12.5a2 2 0 0 1 2-2Z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14 3.75v5h5M8.5 13h7M8.5 16.5h7" />
                    </svg>
                    <span x-cloak x-show="sidebarOpen" x-transition.opacity>Notes</span>
                </a>
            </nav>
        </aside>

        <div class="flex min-h-screen min-w-0 flex-1 flex-col">
            <header class="sticky top-0 z-20 flex h-16 shrink-0 items-center justify-between border-b border-gray-200 bg-white/95 px-4 backdrop-blur sm:px-6 lg:px-8">
                <div class="flex min-w-0 items-center gap-3">
                    <button
                        type="button"
                        x-ref="sidebarToggle"
                        @click="toggleSidebar()"
                        :aria-label="sidebarOpen ? 'Collapse navigation menu' : 'Open navigation menu'"
                        :aria-expanded="sidebarOpen.toString()"
                        aria-controls="primary-navigation"
                        :title="sidebarOpen ? 'Collapse navigation menu' : 'Open navigation menu'"
                        class="-ml-2 inline-flex size-10 shrink-0 items-center justify-center rounded-lg text-gray-600 transition hover:bg-gray-100 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                    >
                        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path stroke-linecap="round" d="M4 6.75h16M4 12h16M4 17.25h16" />
                        </svg>
                    </button>
                    <div class="min-w-0">
                        <p class="truncate text-sm font-semibold text-gray-900">{{ $pageTitle }}</p>
                        <p class="hidden text-xs text-gray-500 sm:block">{{ $pageSubtitle }}</p>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <button
                        type="button"
                        class="inline-flex size-10 items-center justify-center gap-2 rounded-lg text-gray-600 transition hover:bg-gray-100 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 sm:w-auto sm:px-3"
                        data-theme-toggle
                        data-theme-url="{{ route('profile.theme.update') }}"
                        aria-label="Switch to {{ auth()->user()->theme_preference === 'dark' ? 'light' : 'dark' }} mode"
                        aria-pressed="{{ auth()->user()->theme_preference === 'dark' ? 'true' : 'false' }}"
                        title="Switch theme"
                    >
                        <svg data-theme-icon="dark" @class(['size-5', 'hidden' => auth()->user()->theme_preference === 'dark']) viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M20.2 15.2A8.4 8.4 0 0 1 8.8 3.8 8.5 8.5 0 1 0 20.2 15.2Z" />
                        </svg>
                        <svg data-theme-icon="light" @class(['size-5', 'hidden' => auth()->user()->theme_preference !== 'dark']) viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <circle cx="12" cy="12" r="3.75" />
                            <path stroke-linecap="round" d="M12 2.75v1.5m0 15.5v1.5m9.25-9.25h-1.5m-15.5 0h-1.5m15.29-6.54-1.06 1.06M7.02 16.98l-1.06 1.06m13.08 0-1.06-1.06M7.02 7.02 5.96 5.96" />
                        </svg>
                        <span class="hidden text-sm font-medium sm:inline" data-theme-label>{{ auth()->user()->theme_preference === 'dark' ? 'Light theme' : 'Dark theme' }}</span>
                    </button>
                    <span class="sr-only" data-theme-status aria-live="polite"></span>
                    <span class="hidden text-sm text-gray-500 sm:inline">Personal workspace</span>

                    <div
                        x-data="{ open: false, suppressFocusOpen: false }"
                        x-on:mouseenter="open = true"
                        x-on:mouseleave="if (! $el.contains(document.activeElement)) { open = false }"
                        x-on:focusin="if (! suppressFocusOpen) { open = true }"
                        x-on:focusout="if (! $el.contains($event.relatedTarget)) { open = false }"
                        x-on:click.outside="open = false"
                        x-on:keydown.escape.window="if (open) { suppressFocusOpen = true; open = false; $refs.accountButton.focus(); $nextTick(() => { suppressFocusOpen = false }) }"
                        class="relative"
                    >
                        <button
                            type="button"
                            x-ref="accountButton"
                            x-on:click="open = true"
                            x-bind:aria-expanded="open.toString()"
                            aria-controls="account-menu"
                            aria-label="Open account menu for {{ auth()->user()->name }}"
                            title="{{ auth()->user()->name }}"
                            class="flex size-9 items-center justify-center rounded-full bg-gray-100 text-xs font-semibold text-gray-700 ring-1 ring-inset ring-gray-200 transition hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                        >
                            {{ $userInitials }}
                        </button>

                        <div id="account-menu" x-cloak x-show="open" x-transition.opacity class="absolute right-0 top-full z-50 w-48 pt-2">
                            <div class="rounded-xl border border-gray-200 bg-white p-1.5 shadow-lg">
                                <a
                                    href="{{ route('profile.index') }}"
                                    @class([
                                        'block rounded-lg px-3 py-2 text-sm font-medium transition',
                                        'bg-indigo-50 text-indigo-700' => request()->routeIs('profile.*'),
                                        'text-gray-700 hover:bg-gray-100 hover:text-gray-900' => ! request()->routeIs('profile.*'),
                                    ])
                                >
                                    Profile
                                </a>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="w-full rounded-lg px-3 py-2 text-left text-sm font-medium text-gray-700 transition hover:bg-gray-100 hover:text-gray-900">
                                        Sign out
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <main class="flex-1 bg-gray-50 px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
                <div class="mx-auto w-full max-w-[96rem]">
                    @yield('content')
                </div>
            </main>

            <footer class="flex flex-wrap items-center justify-between gap-x-4 gap-y-2 border-t border-gray-200 bg-white px-4 py-3 text-xs text-gray-500 sm:px-6 lg:px-8" aria-label="Application information">
                <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
                    <span class="font-medium text-gray-700">{{ config('app.name', 'HiveTasks') }}</span>
                    <span aria-hidden="true" class="text-gray-300">·</span>
                    <time datetime="{{ now()->toDateString() }}" data-live-date>{{ now()->format('F j, Y') }}</time>
                </div>
                <time class="font-mono tabular-nums text-gray-600" datetime="{{ now()->format('H:i') }}" data-live-clock>{{ now()->format('H:i') }}</time>
            </footer>
        </div>
    </div>
    @livewireScripts
</body>
</html>
