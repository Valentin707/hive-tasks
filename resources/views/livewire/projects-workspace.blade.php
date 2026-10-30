<section
    x-data="{ deleteProjectModalOpen: false, projectDeleteConfirmationText: '', projectDeleteConfirmationInput: '', projectDeleteBoardCount: 0, projectDeleteColumnCount: 0, projectDeleteTaskCount: 0 }"
    x-on:project-delete-opened.window="deleteProjectModalOpen = true; projectDeleteConfirmationText = $event.detail.confirmationText; projectDeleteBoardCount = Number($event.detail.boardCount); projectDeleteColumnCount = Number($event.detail.columnCount); projectDeleteTaskCount = Number($event.detail.taskCount); projectDeleteConfirmationInput = ''"
    x-on:project-deleted.window="deleteProjectModalOpen = false; projectDeleteConfirmationText = ''; projectDeleteConfirmationInput = ''; projectDeleteBoardCount = 0; projectDeleteColumnCount = 0; projectDeleteTaskCount = 0"
    x-on:keydown.escape.window="if (deleteProjectModalOpen) { deleteProjectModalOpen = false; projectDeleteConfirmationInput = ''; $wire.closeDeleteProjectConfirmation() }"
>
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

                <div x-cloak x-show="open" x-transition.opacity class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-gray-950/40 p-4 sm:items-center" @click.self="open = false">
                    <section role="dialog" aria-modal="true" aria-labelledby="new-project-title" class="max-h-[calc(100dvh-2rem)] w-full max-w-2xl overflow-y-auto rounded-2xl bg-white p-6 shadow-xl sm:p-7">
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
                                <p class="form-label">Description <span class="font-normal text-gray-400">(optional)</span></p>
                                <div
                                    x-data="quillRichTextEditor({ placeholder: 'Add project details...', ariaLabel: 'New project description', minHeight: '10rem' })"
                                    x-init="$nextTick(() => initialize())"
                                    x-on:project-created.window="clear()"
                                >
                                    <div class="quill-rich-text-editor" wire:ignore>
                                        <div x-ref="editor"></div>
                                    </div>
                                    <textarea x-ref="contentInput" wire:model="description" aria-hidden="true" tabindex="-1" class="hidden">{{ $description }}</textarea>
                                    <textarea x-ref="initialHtml" aria-hidden="true" tabindex="-1" class="hidden">{{ $descriptionHtml }}</textarea>
                                </div>
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
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ($projects as $project)
                <section wire:key="project-{{ $project->id }}" class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm sm:p-6" aria-labelledby="project-{{ $project->id }}-title">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                        <div class="min-w-0 flex-1">
                            <div class="flex items-start justify-between gap-3">
                                <h2 id="project-{{ $project->id }}-title" class="truncate text-lg font-semibold text-gray-900">{{ $project->name }}</h2>
                                <button
                                    type="button"
                                    wire:click="openDeleteProjectConfirmation({{ $project->id }})"
                                    wire:loading.attr="disabled"
                                    wire:target="openDeleteProjectConfirmation"
                                    aria-label="Delete project: {{ $project->name }}"
                                    title="Delete project"
                                    class="inline-flex size-9 shrink-0 items-center justify-center rounded-lg bg-red-50 text-red-600 transition hover:bg-red-100 focus:outline-none focus:ring-2 focus:ring-red-500 dark:bg-red-950/40 dark:text-red-400 dark:hover:bg-red-950/70"
                                >
                                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M10 11v6m4-6v6M6 7l1 13h10l1-13M9 7V4h6v3" />
                                    </svg>
                                </button>
                            </div>
                            <p class="mt-2 flex flex-wrap items-center gap-x-2 gap-y-1 text-sm text-gray-500">
                                <time datetime="{{ $project->created_at->toDateString() }}">Created {{ $project->created_at->format('M j, Y') }}</time>
                                <span aria-hidden="true">·</span>
                                <span>{{ $project->tasks_count }} {{ \Illuminate\Support\Str::plural('task', $project->tasks_count) }}</span>
                            </p>
                        </div>
                    </div>

                    @if ($project->boards->isNotEmpty())
                        <div class="mt-5 grid grid-cols-1 gap-3">
                            @foreach ($project->boards as $board)
                                <a wire:key="board-{{ $board->id }}" href="{{ route('projects.board', ['project' => $project, 'board' => $board]) }}" class="group flex w-full items-center justify-between gap-4 rounded-xl border border-gray-200 p-4 transition duration-150 hover:border-indigo-300 hover:bg-indigo-50/50">
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

    @if ($deletingProjectId !== null)
        <div x-cloak x-show="deleteProjectModalOpen" x-transition.opacity class="fixed inset-0 z-[70] flex items-start justify-center overflow-y-auto bg-gray-950/50 p-4 sm:items-center" @click.self="deleteProjectModalOpen = false; projectDeleteConfirmationInput = ''; $wire.closeDeleteProjectConfirmation()">
            <section role="alertdialog" aria-modal="true" aria-labelledby="delete-project-title" aria-describedby="delete-project-description" class="max-h-[calc(100dvh-2rem)] w-full max-w-lg overflow-y-auto rounded-2xl bg-white p-6 shadow-xl sm:p-7">
                <div class="mb-5 flex items-start gap-4">
                    <div class="flex size-11 shrink-0 items-center justify-center rounded-full bg-red-100 text-red-700 dark:bg-red-950/60 dark:text-red-300">
                        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M10.3 3.9 2.8 17a2 2 0 0 0 1.7 3h15a2 2 0 0 0 1.7-3l-7.5-13.1a2 2 0 0 0-3.4 0Z" />
                        </svg>
                    </div>
                    <div class="min-w-0">
                        <h2 id="delete-project-title" class="text-lg font-semibold text-gray-900">Delete this project?</h2>
                        <p class="mt-1 text-sm text-gray-600">You are about to permanently delete "<span class="font-semibold text-gray-900" x-text="projectDeleteConfirmationText"></span>".</p>
                    </div>
                </div>

                <div class="mb-5 rounded-xl border border-red-200 bg-red-50 p-4 dark:border-red-900/60 dark:bg-red-950/30">
                    <p id="delete-project-description" class="text-sm leading-6 text-red-800 dark:text-red-200">This action cannot be undone. The project and all of its boards, columns, and tasks will be permanently removed.</p>
                    <ul class="mt-3 space-y-1.5 text-sm font-medium text-red-800 dark:text-red-200">
                        <li class="flex items-center justify-between gap-4"><span>Boards</span><span x-text="projectDeleteBoardCount"></span></li>
                        <li class="flex items-center justify-between gap-4"><span>Columns</span><span x-text="projectDeleteColumnCount"></span></li>
                        <li class="flex items-center justify-between gap-4"><span>Tasks</span><span x-text="projectDeleteTaskCount"></span></li>
                    </ul>
                </div>

                <form wire:submit="deleteProject">
                    <div class="mb-6">
                        <label for="project-delete-confirmation" class="form-label">Type "<span class="rounded bg-gray-100 px-1.5 py-0.5 font-mono font-semibold text-gray-900 dark:bg-gray-800 dark:text-gray-100" x-text="projectDeleteConfirmationText"></span>" to confirm</label>
                        <input id="project-delete-confirmation" type="text" wire:model="projectDeleteConfirmation" x-on:input="projectDeleteConfirmationInput = $event.target.value" autocomplete="off" required autofocus class="form-input" @error('projectDeleteConfirmation') aria-invalid="true" aria-describedby="project-delete-confirmation-error" @enderror>
                        @error('projectDeleteConfirmation')
                            <p id="project-delete-confirmation-error" class="form-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                        <button type="button" wire:click="closeDeleteProjectConfirmation" @click="deleteProjectModalOpen = false; projectDeleteConfirmationInput = ''" class="button-secondary">Cancel</button>
                        <button type="submit" x-bind:disabled="projectDeleteConfirmationInput !== projectDeleteConfirmationText" class="inline-flex items-center justify-center gap-2 rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-red-700 focus:outline-none focus:ring-4 focus:ring-red-500/25 disabled:cursor-not-allowed disabled:opacity-50" wire:loading.attr="disabled" wire:target="deleteProject">
                            <span wire:loading.remove wire:target="deleteProject">Delete project</span>
                            <span wire:loading wire:target="deleteProject">Deleting...</span>
                        </button>
                    </div>
                </form>
            </section>
        </div>
    @endif
</section>
