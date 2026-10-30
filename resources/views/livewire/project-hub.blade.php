<div>
    <div class="mb-7 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="mb-2 text-sm font-medium text-indigo-600 dark:text-indigo-400">Personal workspace</p>
            <h1 class="text-2xl font-semibold tracking-tight text-gray-900 sm:text-3xl">Project Hub</h1>
            <p class="mt-2 max-w-2xl text-sm leading-6 text-gray-500">A live overview of your projects, tasks, and notes.</p>
        </div>
        <a href="{{ route('projects.index') }}" class="inline-flex items-center gap-2 self-start rounded-lg px-3 py-2 text-sm font-semibold text-indigo-700 transition hover:bg-indigo-50 sm:self-auto dark:text-indigo-300 dark:hover:bg-indigo-950/50">
            View projects
            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14m-6-6 6 6-6 6" />
            </svg>
        </a>
    </div>

    <section aria-label="Workspace metrics" class="mb-7 grid grid-cols-1 gap-4 sm:grid-cols-3 sm:gap-5">
        <article class="rounded-2xl bg-white p-5 shadow-sm shadow-gray-900/5 sm:p-6 dark:bg-gray-100 dark:shadow-black/20">
            <div class="flex items-center justify-between gap-4">
                <p class="text-sm font-medium text-gray-500">Total tasks</p>
                <span class="flex size-10 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 dark:bg-indigo-950/60 dark:text-indigo-300">
                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <rect x="4" y="4" width="16" height="16" rx="3" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="m8 12 2.5 2.5L16 9" />
                    </svg>
                </span>
            </div>
            <p class="mt-5 font-mono text-4xl font-semibold tracking-tight text-gray-900 tabular-nums">{{ number_format($totalTaskCount) }}</p>
            <p class="mt-2 text-sm text-gray-500">Across all project boards</p>
        </article>

        <article class="rounded-2xl bg-white p-5 shadow-sm shadow-gray-900/5 sm:p-6 dark:bg-gray-100 dark:shadow-black/20">
            <div class="flex items-center justify-between gap-4">
                <p class="text-sm font-medium text-gray-500">Notes</p>
                <span class="flex size-10 items-center justify-center rounded-xl bg-sky-50 text-sky-600 dark:bg-sky-950/60 dark:text-sky-300">
                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M7 3.75h7l5 5v11.5H7a2 2 0 0 1-2-2v-12.5a2 2 0 0 1 2-2Z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14 3.75v5h5M8.5 13h7M8.5 16.5h7" />
                    </svg>
                </span>
            </div>
            <p class="mt-5 font-mono text-4xl font-semibold tracking-tight text-gray-900 tabular-nums">{{ number_format($totalNoteCount) }}</p>
            <p class="mt-2 text-sm text-gray-500">Saved in your notes workspace</p>
        </article>

        <article class="rounded-2xl bg-white p-5 shadow-sm shadow-gray-900/5 sm:p-6 dark:bg-gray-100 dark:shadow-black/20">
            <div class="flex items-center justify-between gap-4">
                <p class="text-sm font-medium text-gray-500">Workspace completion</p>
                <span class="flex size-10 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-300">
                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 18V6m0 12h16M7 15l4-4 3 2 5-6" />
                    </svg>
                </span>
            </div>
            <p class="mt-5 font-mono text-4xl font-semibold tracking-tight text-gray-900 tabular-nums">{{ $workspaceCompletionPercentage }}<span class="ml-1 text-2xl text-gray-400">%</span></p>
            <p class="mt-2 text-sm text-gray-500">Tasks in Done or Deployed columns</p>
        </article>
    </section>

    <div class="grid gap-5 xl:grid-cols-[minmax(0,1.55fr)_minmax(19rem,0.9fr)]">
        <section aria-labelledby="recent-tasks-heading" class="min-w-0 rounded-2xl bg-white p-5 shadow-sm shadow-gray-900/5 sm:p-6 dark:bg-gray-100 dark:shadow-black/20">
            <div class="mb-2 flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 id="recent-tasks-heading" class="text-base font-semibold text-gray-900">Recent task monitor</h2>
                    <p class="mt-1 text-sm text-gray-500">The latest created or updated tasks across your workspace.</p>
                </div>
                <span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-600 dark:bg-gray-200 dark:text-gray-700">{{ $recentTasks->count() }} {{ \Illuminate\Support\Str::plural('task', $recentTasks->count()) }}</span>
            </div>

            @if ($recentTasks->isNotEmpty())
                <ul class="mt-3 divide-y divide-gray-100 dark:divide-gray-200">
                    @foreach ($recentTasks as $task)
                        @php
                            $taskBoard = $task->column->board;
                            $taskProject = $taskBoard->project;
                        @endphp
                        <li wire:key="recent-task-{{ $task->id }}" class="py-4 first:pt-3 last:pb-1">
                            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between sm:gap-5">
                                <div class="min-w-0">
                                    <a href="{{ route('projects.board', ['project' => $taskProject, 'board' => $taskBoard]) }}" class="block break-words text-sm font-semibold text-gray-800 transition hover:text-indigo-600 dark:hover:text-indigo-400">
                                        {{ $task->title }}
                                    </a>
                                    <p class="mt-1.5 flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-gray-500">
                                        <span class="max-w-full truncate">{{ $taskProject->name }}</span>
                                        <span aria-hidden="true" class="text-gray-300">·</span>
                                        <span>{{ $task->column->name }}</span>
                                        <span aria-hidden="true" class="text-gray-300">·</span>
                                        <span>{{ $taskBoard->name }}</span>
                                    </p>
                                </div>
                                <time datetime="{{ $task->updated_at?->toIso8601String() }}" class="shrink-0 text-xs text-gray-400" title="Created {{ $task->created_at?->format('M j, Y g:i A') }}">
                                    Last activity {{ $task->updated_at?->diffForHumans() ?? 'recently' }}
                                </time>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @else
                <div class="mt-5 rounded-xl bg-gray-50 px-5 py-10 text-center dark:bg-gray-50">
                    <span class="mx-auto flex size-11 items-center justify-center rounded-xl bg-white text-gray-400 shadow-sm dark:bg-gray-100">
                        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <rect x="4" y="4" width="16" height="16" rx="3" />
                            <path stroke-linecap="round" d="M8 9h8M8 13h5" />
                        </svg>
                    </span>
                    <h3 class="mt-3 text-sm font-semibold text-gray-800">No tasks to monitor yet</h3>
                    <p class="mt-1 text-sm text-gray-500">Add a task to one of your project boards to see activity here.</p>
                </div>
            @endif
        </section>

        <section aria-labelledby="project-health-heading" class="min-w-0 rounded-2xl bg-white p-5 shadow-sm shadow-gray-900/5 sm:p-6 dark:bg-gray-100 dark:shadow-black/20">
            <div class="mb-2 flex items-start justify-between gap-3">
                <div>
                    <h2 id="project-health-heading" class="text-base font-semibold text-gray-900">Project health</h2>
                    <p class="mt-1 text-sm text-gray-500">Completion by project.</p>
                </div>
                <span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-600 dark:bg-gray-200 dark:text-gray-700">{{ count($activeProjects) }}</span>
            </div>

            @if ($activeProjects !== [])
                <ul class="mt-3 space-y-5">
                    @foreach ($activeProjects as $project)
                        <li wire:key="project-health-{{ $project['id'] }}" class="rounded-xl bg-gray-50/80 p-4 dark:bg-gray-50">
                            <div class="flex items-start justify-between gap-3">
                                @if ($project['board_url'] !== null)
                                    <a href="{{ $project['board_url'] }}" class="min-w-0 break-words text-sm font-semibold text-gray-800 transition hover:text-indigo-600 dark:hover:text-indigo-400">
                                        {{ $project['name'] }}
                                    </a>
                                @else
                                    <span class="min-w-0 break-words text-sm font-semibold text-gray-800">{{ $project['name'] }}</span>
                                @endif
                                <span class="shrink-0 font-mono text-sm font-semibold tabular-nums text-gray-800">{{ $project['completion_percentage'] }}%</span>
                            </div>
                            <div
                                class="mt-3 h-2 overflow-hidden rounded-full bg-gray-200 dark:bg-gray-300"
                                role="progressbar"
                                aria-label="{{ $project['name'] }} completion"
                                aria-valuemin="0"
                                aria-valuemax="100"
                                aria-valuenow="{{ $project['completion_percentage'] }}"
                            >
                                <div class="h-full rounded-full bg-indigo-600 transition-[width] duration-500 dark:bg-indigo-400" style="width: {{ $project['completion_percentage'] }}%"></div>
                            </div>
                            <p class="mt-2.5 text-xs text-gray-500">{{ $project['completed_task_count'] }} of {{ $project['task_count'] }} {{ \Illuminate\Support\Str::plural('task', $project['task_count']) }} complete</p>
                        </li>
                    @endforeach
                </ul>
            @else
                <div class="mt-5 rounded-xl bg-gray-50 px-5 py-10 text-center dark:bg-gray-50">
                    <h3 class="text-sm font-semibold text-gray-800">No projects yet</h3>
                    <p class="mt-1 text-sm text-gray-500">Create a project to start tracking its health.</p>
                    <a href="{{ route('projects.index') }}" class="mt-4 inline-flex items-center justify-center rounded-lg bg-indigo-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-indigo-700">Open projects</a>
                </div>
            @endif
        </section>
    </div>
</div>
