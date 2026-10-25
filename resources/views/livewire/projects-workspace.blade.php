<section>
    <div class="mb-8 flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="mb-2 text-sm font-medium text-indigo-600">Workspace</p>
            <h1 class="text-2xl font-semibold tracking-tight text-gray-900 sm:text-3xl">Projects</h1>
            <p class="mt-2 text-sm text-gray-500">Choose a project board to continue working.</p>
        </div>

        <div class="flex w-full flex-col gap-3 sm:w-auto sm:flex-row">
            <label class="relative min-w-0 flex-1 sm:w-64" for="project-search">
                <span class="sr-only">Search projects</span>
                <svg class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <circle cx="10.8" cy="10.8" r="6.3" />
                    <path stroke-linecap="round" d="m15.5 15.5 4.25 4.25" />
                </svg>
                <input id="project-search" type="search" wire:model.live.debounce.300ms="search" placeholder="Search projects..." class="form-input pl-9">
            </label>

            <div x-data="{ open: false }" x-on:project-created.window="open = false" x-on:keydown.escape.window="open = false">
                <button type="button" @click="open = true" class="button-primary w-full whitespace-nowrap sm:w-auto">
                    <span aria-hidden="true">+</span>
                    New Project
                </button>

                <div x-cloak x-show="open" x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center bg-gray-950/40 p-4" @click.self="open = false">
                    <section role="dialog" aria-modal="true" aria-labelledby="new-project-title" class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-xl sm:p-7">
                        <div class="mb-6 flex items-start justify-between gap-4">
                            <div>
                                <h2 id="new-project-title" class="text-lg font-semibold text-gray-900">Create a project</h2>
                                <p class="mt-1 text-sm text-gray-500">Give your workspace a name and an optional description.</p>
                            </div>
                            <button type="button" @click="open = false" class="rounded-lg p-1 text-gray-400 transition hover:bg-gray-100 hover:text-gray-700" aria-label="Close dialog">×</button>
                        </div>

                        <form wire:submit="createProject">
                            <div class="mb-5">
                                <label for="project-name" class="form-label">Project name</label>
                                <input id="project-name" type="text" wire:model="name" maxlength="255" required autofocus class="form-input" @error('name') aria-invalid="true" aria-describedby="project-name-error" @enderror>
                                @error('name')
                                    <p id="project-name-error" class="form-error">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="mb-6">
                                <label for="project-description" class="form-label">Description <span class="font-normal text-gray-400">(optional)</span></label>
                                <textarea id="project-description" wire:model="description" rows="4" maxlength="20000" class="form-input resize-y" @error('description') aria-invalid="true" aria-describedby="project-description-error" @enderror></textarea>
                                @error('description')
                                    <p id="project-description-error" class="form-error">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                                <button type="button" @click="open = false" class="button-secondary">Cancel</button>
                                <button type="submit" class="button-primary" wire:loading.attr="disabled" wire:target="createProject">
                                    <span wire:loading.remove wire:target="createProject">Create project</span>
                                    <span wire:loading wire:target="createProject">Creating...</span>
                                </button>
                            </div>
                        </form>
                    </section>
                </div>
            </div>
        </div>
    </div>

    @if ($projects->isNotEmpty())
        <div class="space-y-5">
            @foreach ($projects as $project)
                <section wire:key="project-{{ $project->id }}" class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm sm:p-6" aria-labelledby="project-{{ $project->id }}-title">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                        <div class="min-w-0">
                            <h2 id="project-{{ $project->id }}-title" class="truncate text-lg font-semibold text-gray-900">{{ $project->name }}</h2>
                            @if ($project->description)
                                <p class="mt-1 max-w-2xl text-sm leading-6 text-gray-500">{{ $project->description }}</p>
                            @endif
                        </div>
                        <span class="inline-flex w-fit shrink-0 items-center rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-600">
                            {{ $project->boards_count }} {{ \Illuminate\Support\Str::plural('board', $project->boards_count) }}
                        </span>
                    </div>

                    @if ($project->boards->isNotEmpty())
                        <div class="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                            @foreach ($project->boards as $board)
                                <a wire:key="board-{{ $board->id }}" href="{{ route('projects.board', ['project' => $project, 'board' => $board]) }}" class="group flex items-center justify-between gap-4 rounded-xl border border-gray-200 p-4 transition duration-150 hover:border-indigo-300 hover:bg-indigo-50/50">
                                    <div class="flex min-w-0 items-center gap-3">
                                        <span class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600 transition group-hover:bg-indigo-100">
                                            <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                                <rect x="3.75" y="4.75" width="7" height="6.5" rx="1.5" />
                                                <rect x="13.25" y="4.75" width="7" height="14.5" rx="1.5" />
                                                <rect x="3.75" y="14.25" width="7" height="5" rx="1.5" />
                                            </svg>
                                        </span>
                                        <span class="truncate text-sm font-medium text-gray-800">{{ $board->name }}</span>
                                    </div>
                                    <svg class="size-4 shrink-0 text-gray-400 transition group-hover:translate-x-0.5 group-hover:text-indigo-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m9.5 18 6-6-6-6" />
                                    </svg>
                                </a>
                            @endforeach
                        </div>
                    @else
                        <p class="mt-5 rounded-xl border border-dashed border-gray-300 bg-gray-50 px-4 py-5 text-sm text-gray-500">This project does not have any boards yet.</p>
                    @endif
                </section>
            @endforeach
        </div>
    @else
        <div class="rounded-2xl border border-dashed border-gray-300 bg-white px-6 py-14 text-center">
            <div class="mx-auto flex size-12 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600">
                <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <rect x="3.75" y="4.75" width="7" height="6.5" rx="1.5" />
                    <rect x="13.25" y="4.75" width="7" height="14.5" rx="1.5" />
                    <rect x="3.75" y="14.25" width="7" height="5" rx="1.5" />
                </svg>
            </div>
            <h2 class="mt-4 text-sm font-semibold text-gray-900">{{ trim($search) === '' ? 'No projects yet' : 'No matching projects' }}</h2>
            <p class="mt-1 text-sm text-gray-500">{{ trim($search) === '' ? 'Create a project to start organizing your work.' : 'Try another project name.' }}</p>
        </div>
    @endif
</section>
