@extends('layouts.app')

@section('title', 'My First Project')

@section('content')
    <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="mb-2 text-sm font-medium text-indigo-600">Your workspace</p>
            <h1 class="text-2xl font-semibold tracking-tight text-gray-900 sm:text-3xl">My First Project</h1>
            <p class="mt-2 text-sm text-gray-500">A clear view of what your team is working on.</p>
        </div>
        <button type="button" class="button-primary">
            <svg class="mr-2 size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" d="M12 5v14M5 12h14" />
            </svg>
            New task
        </button>
    </div>

    <section aria-label="Kanban board" class="grid gap-5 md:grid-cols-2 xl:grid-cols-4">
        @php
            $boardColumns = [
                ['name' => 'Todo', 'count' => 2, 'tasks' => ['Create the first project plan', 'Sketch the Kanban board layout'], 'accent' => 'bg-gray-400'],
                ['name' => 'In Progress', 'count' => 2, 'tasks' => ['Build the task data model', 'Add sample board content'], 'accent' => 'bg-indigo-500'],
                ['name' => 'Done', 'count' => 0, 'tasks' => [], 'accent' => 'bg-emerald-500'],
                ['name' => 'Deployed', 'count' => 0, 'tasks' => [], 'accent' => 'bg-violet-500'],
            ];
        @endphp

        @foreach ($boardColumns as $column)
            <section class="min-w-0 rounded-xl border border-gray-200 bg-gray-100/70 p-3.5" aria-label="{{ $column['name'] }} column">
                <div class="mb-3 flex items-center justify-between px-1">
                    <div class="flex items-center gap-2.5">
                        <span class="size-2 rounded-full {{ $column['accent'] }}"></span>
                        <h2 class="text-sm font-semibold text-gray-800">{{ $column['name'] }}</h2>
                        <span class="rounded-md bg-white px-1.5 py-0.5 text-xs font-medium text-gray-500 ring-1 ring-inset ring-gray-200">{{ $column['count'] }}</span>
                    </div>
                    <button type="button" class="rounded-md p-1 text-gray-400 transition hover:bg-white hover:text-gray-700" aria-label="More options for {{ $column['name'] }}">
                        <svg class="size-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                            <circle cx="5" cy="12" r="1.5" />
                            <circle cx="12" cy="12" r="1.5" />
                            <circle cx="19" cy="12" r="1.5" />
                        </svg>
                    </button>
                </div>

                <div class="space-y-3">
                    @forelse ($column['tasks'] as $task)
                        <article class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm transition hover:border-indigo-200 hover:shadow-md">
                            <p class="text-sm font-medium leading-5 text-gray-800">{{ $task }}</p>
                            <div class="mt-4 flex items-center justify-between">
                                <span class="rounded-full bg-indigo-50 px-2 py-1 text-xs font-medium text-indigo-700">Task</span>
                                <span class="flex size-7 items-center justify-center rounded-full bg-gray-100 text-[10px] font-semibold text-gray-600" aria-label="HiveTasks member">HT</span>
                            </div>
                        </article>
                    @empty
                        <div class="rounded-lg border border-dashed border-gray-300 bg-white/60 px-3 py-6 text-center">
                            <p class="text-xs text-gray-400">No tasks here yet</p>
                        </div>
                    @endforelse
                </div>

                <button type="button" class="mt-3 flex w-full items-center gap-2 rounded-lg px-2 py-2 text-left text-sm font-medium text-gray-500 transition hover:bg-white hover:text-gray-800">
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" d="M12 5v14M5 12h14" />
                    </svg>
                    Add a task
                </button>
            </section>
        @endforeach
    </section>
@endsection
