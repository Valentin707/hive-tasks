<div
    data-kanban-board
    x-data="{ taskEditModalOpen: false, createColumnModalOpen: false, editColumnModalOpen: false, deleteColumnModalOpen: false, columnDeleteName: '', columnDeleteTaskCount: 0, deleteConfirmationInput: '' }"
    x-on:task-edit-opened.window="taskEditModalOpen = true"
    x-on:task-updated.window="taskEditModalOpen = false"
    x-on:column-create-opened.window="createColumnModalOpen = true"
    x-on:column-created.window="createColumnModalOpen = false"
    x-on:column-edit-opened.window="editColumnModalOpen = true"
    x-on:column-updated.window="editColumnModalOpen = false"
    x-on:column-delete-opened.window="deleteColumnModalOpen = true; columnDeleteName = $event.detail.columnName; columnDeleteTaskCount = Number($event.detail.taskCount); deleteConfirmationInput = ''"
    x-on:column-deleted.window="deleteColumnModalOpen = false; columnDeleteName = ''; columnDeleteTaskCount = 0; deleteConfirmationInput = ''"
    x-on:keydown.escape.window="if (taskEditModalOpen) { taskEditModalOpen = false; $wire.closeTaskEditModal() } if (createColumnModalOpen) { createColumnModalOpen = false; $wire.closeCreateColumnModal() } if (editColumnModalOpen) { editColumnModalOpen = false; $wire.closeEditColumnModal() } if (deleteColumnModalOpen) { deleteColumnModalOpen = false; deleteConfirmationInput = ''; $wire.closeDeleteColumnConfirmation() }"
>
    <div class="space-y-10">
        @forelse ($project->boards as $board)
            <section wire:key="kanban-board-{{ $board->id }}" data-kanban-board-section data-board-id="{{ $board->id }}" x-data="{ reorderMode: false }" x-bind:data-column-reorder-mode="reorderMode ? 'true' : 'false'" x-on:column-reorder-start="reorderMode = true" x-on:column-reorder-end="reorderMode = false" aria-labelledby="board-{{ $board->id }}-title">
                <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                    <h2 id="board-{{ $board->id }}-title" class="text-base font-semibold text-gray-900">{{ $board->name }}</h2>
                    <div class="flex items-center gap-3">
                        <p x-cloak x-show="reorderMode" class="text-xs text-gray-500">Release the handle to finish</p>
                        <button
                            type="button"
                            wire:click="openCreateColumnModal({{ $board->id }})"
                            wire:loading.attr="disabled"
                            wire:target="openCreateColumnModal"
                            class="button-secondary gap-1.5 px-3 py-1.5 text-xs sm:text-sm"
                        >
                            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path stroke-linecap="round" d="M12 5v14M5 12h14" />
                            </svg>
                            Add column
                        </button>
                        <span class="text-sm text-gray-500" data-board-task-count>{{ $board->columns->sum(fn ($column) => $column->tasks->count()) }} tasks</span>
                    </div>
                </div>

                <div class="kanban-columns flex items-stretch gap-5 overflow-x-auto overflow-y-hidden overscroll-x-contain pb-3 touch-pan-x" data-kanban-columns>
                    @foreach ($board->columns as $column)
                        <section wire:key="kanban-column-{{ $column->id }}" data-kanban-column-wrapper data-column-id="{{ $column->id }}" class="flex h-full w-[min(20rem,calc(100vw-2rem))] shrink-0 flex-col rounded-xl border border-gray-200 bg-gray-100/70 p-3.5 shadow-sm transition duration-150" x-bind:class="reorderMode ? 'border-indigo-400 ring-2 ring-indigo-100 shadow-md' : ''" aria-labelledby="column-{{ $column->id }}-title">
                            <div class="mb-3 flex items-center justify-between gap-2 px-1">
                                <div class="flex min-w-0 items-center gap-2.5">
                                    <span class="size-2 shrink-0 rounded-full" style="background-color: {{ $column->color }}"></span>
                                    <h3 id="column-{{ $column->id }}-title" class="truncate text-sm font-semibold text-gray-800">{{ $column->name }}</h3>
                                    <span class="rounded-md bg-white px-1.5 py-0.5 text-xs font-medium text-gray-500 ring-1 ring-inset ring-gray-200" data-column-task-count>{{ $column->tasks->count() }}</span>
                                </div>
                                <div class="relative flex shrink-0 items-center gap-1" x-data="{ menuOpen: false }" x-on:click.outside="menuOpen = false" x-on:keydown.escape.window="menuOpen = false">
                                    <button
                                        type="button"
                                        data-column-drag-handle
                                        :aria-pressed="reorderMode.toString()"
                                        aria-label="Hold and drag to reorder this column"
                                        title="Hold and drag to reorder"
                                        x-bind:class="reorderMode ? 'cursor-grab text-indigo-600 hover:bg-indigo-100 active:cursor-grabbing' : 'text-gray-400 hover:bg-gray-200'"
                                        class="touch-none inline-flex size-8 shrink-0 select-none items-center justify-center rounded-lg transition focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                    >
                                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                            <path stroke-linecap="round" d="M4 6h16M4 12h16M4 18h16" />
                                        </svg>
                                    </button>
                                    <button
                                        type="button"
                                        aria-haspopup="menu"
                                        aria-controls="column-menu-{{ $column->id }}"
                                        x-bind:aria-expanded="menuOpen.toString()"
                                        aria-label="Column options"
                                        title="Column options"
                                        @click="menuOpen = ! menuOpen"
                                        class="inline-flex size-8 shrink-0 items-center justify-center rounded-lg text-gray-500 transition hover:bg-gray-200 hover:text-gray-800 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:hover:bg-gray-700"
                                    >
                                        <svg class="size-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                            <circle cx="5" cy="12" r="1.7" />
                                            <circle cx="12" cy="12" r="1.7" />
                                            <circle cx="19" cy="12" r="1.7" />
                                        </svg>
                                    </button>
                                    <div id="column-menu-{{ $column->id }}" x-cloak x-show="menuOpen" x-transition.opacity role="menu" class="absolute right-0 top-full z-30 mt-1 w-44 rounded-xl border border-gray-200 bg-white p-1.5 shadow-lg">
                                        <button type="button" role="menuitem" wire:click="openEditColumnModal({{ $column->id }})" @click="menuOpen = false" class="flex w-full items-center rounded-lg px-3 py-2 text-left text-sm font-medium text-gray-700 transition hover:bg-gray-100 hover:text-gray-900">
                                            Edit column
                                        </button>
                                        <button type="button" role="menuitem" wire:click="openDeleteColumnConfirmation({{ $column->id }})" @click="menuOpen = false" class="flex w-full items-center rounded-lg px-3 py-2 text-left text-sm font-medium text-red-600 transition hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-950/40">
                                            Delete column
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div wire:ignore wire:key="kanban-column-dropzone-{{ $column->id }}" class="flex min-h-0 flex-1 flex-col gap-3 overflow-y-auto overscroll-contain rounded-lg" data-kanban-column data-column-id="{{ $column->id }}">
                                @foreach ($column->tasks as $task)
                                    <article class="draggable relative cursor-grab rounded-lg border border-gray-200 bg-white p-4 shadow-sm transition hover:border-indigo-200 hover:shadow-md active:cursor-grabbing" data-item-id="{{ $task->id }}">
                                        <button type="button" data-task-edit-button data-task-id="{{ $task->id }}" aria-label="Open task: {{ $task->title }}" class="block w-full rounded pr-10 text-left focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500">
                                            <span data-task-title class="block text-sm font-medium leading-5 text-gray-800">{{ $task->title }}</span>
                                            <time datetime="{{ $task->created_at->toIso8601String() }}" data-task-created-at class="mt-2 block text-xs text-gray-500">Created {{ $task->created_at->format('M j, Y') }}</time>
                                        </button>
                                        <button
                                            type="button"
                                            data-task-delete-button
                                            data-task-id="{{ $task->id }}"
                                            data-task-title="{{ $task->title }}"
                                            draggable="false"
                                            aria-label="Delete task: {{ $task->title }}"
                                            title="Delete task"
                                            class="absolute right-2 top-2 inline-flex size-8 shrink-0 items-center justify-center rounded-lg bg-red-50 text-red-600 transition hover:bg-red-100 focus:outline-none focus:ring-2 focus:ring-red-500 dark:bg-red-950/40 dark:text-red-400 dark:hover:bg-red-950/70"
                                        >
                                            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M10 11v6m4-6v6M6 7l1 13h10l1-13M9 7V4h6v3" />
                                            </svg>
                                        </button>
                                    </article>
                                @endforeach
                                <div data-column-empty-state @class([
                                    'hidden' => $column->tasks->isNotEmpty(),
                                    'rounded-lg border border-dashed border-gray-300 bg-white/60 px-3 py-6 text-center',
                                ])>
                                    <p class="text-xs text-gray-400">Drop tasks here</p>
                                </div>
                            </div>

                            <div wire:key="task-creator-{{ $column->id }}" x-data="{ open: false }" x-on:task-created.window="if (Number($event.detail.columnId) === {{ $column->id }}) open = false" x-on:keydown.escape.window="open = false">
                                <button type="button" wire:click="openTaskModal({{ $column->id }})" @click="open = true" class="button-secondary mt-3 flex w-full items-center justify-center gap-2 border-dashed py-2.5 text-sm">
                                    <span aria-hidden="true" class="text-lg leading-none">+</span>
                                    Add Task
                                </button>

                                <div x-cloak x-show="open" x-transition.opacity class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-gray-950/40 p-4 sm:items-center" @click.self="open = false">
                                    <section role="dialog" aria-modal="true" aria-labelledby="new-task-title-{{ $column->id }}" class="max-h-[calc(100dvh-2rem)] w-full max-w-2xl overflow-y-auto rounded-2xl bg-white p-6 shadow-xl sm:p-7">
                                        <div class="mb-6 flex items-start justify-between gap-4">
                                            <div>
                                                <h2 id="new-task-title-{{ $column->id }}" class="text-lg font-semibold text-gray-900">Add task to {{ $column->name }}</h2>
                                                <p class="mt-1 text-sm text-gray-500">The new task will be added to the end of this column.</p>
                                            </div>
                                            <button type="button" @click="open = false" class="rounded-lg p-1 text-gray-400 transition hover:bg-gray-100 hover:text-gray-700" aria-label="Close dialog">×</button>
                                        </div>

                                        <form wire:submit="createTask">
                                            <div class="mb-5">
                                                <label for="task-title-{{ $column->id }}" class="form-label">Title</label>
                                                <input id="task-title-{{ $column->id }}" type="text" wire:model="taskTitle" maxlength="255" required class="form-input" @error('taskTitle') aria-invalid="true" aria-describedby="task-title-error-{{ $column->id }}" @enderror>
                                                @error('taskTitle')
                                                    <p id="task-title-error-{{ $column->id }}" class="form-error">{{ $message }}</p>
                                                @enderror
                                            </div>

                                            <div class="mb-6">
                                                <p class="form-label">Description <span class="font-normal text-gray-400">(optional)</span></p>
                                                <div
                                                    x-data="quillRichTextEditor({ placeholder: 'Add task details...', ariaLabel: 'Task description', minHeight: '10rem' })"
                                                    x-init="$nextTick(() => initialize())"
                                                    x-on:task-create-opened.window="if (Number($event.detail.columnId) === {{ $column->id }}) clear()"
                                                    x-on:task-created.window="if (Number($event.detail.columnId) === {{ $column->id }}) clear()"
                                                >
                                                    <div class="quill-rich-text-editor" wire:ignore>
                                                        <div x-ref="editor"></div>
                                                    </div>
                                                    <textarea x-ref="contentInput" wire:model="taskDescription" aria-hidden="true" tabindex="-1" class="hidden">{{ $taskDescription }}</textarea>
                                                    <textarea x-ref="initialHtml" aria-hidden="true" tabindex="-1" class="hidden">{{ $taskDescriptionHtml }}</textarea>
                                                </div>
                                                @error('taskDescription')
                                                    <p id="task-description-error-{{ $column->id }}" class="form-error">{{ $message }}</p>
                                                @enderror
                                            </div>

                                            <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                                                <button type="button" @click="open = false" class="button-secondary">Cancel</button>
                                                <button type="submit" class="button-primary" wire:loading.attr="disabled" wire:target="createTask">
                                                    <span wire:loading.remove wire:target="createTask">Create task</span>
                                                    <span wire:loading wire:target="createTask">Creating...</span>
                                                </button>
                                            </div>
                                        </form>
                                    </section>
                                </div>
                            </div>
                        </section>
                    @endforeach
                </div>
            </section>
        @empty
            <div class="rounded-xl border border-dashed border-gray-300 bg-white p-10 text-center">
                <p class="text-sm font-medium text-gray-700">No boards yet</p>
                <p class="mt-1 text-sm text-gray-500">Add a board and columns to start organizing tasks.</p>
            </div>
        @endforelse
    </div>

    @if ($newColumnBoardId !== null)
        <div x-cloak x-show="createColumnModalOpen" x-transition.opacity class="fixed inset-0 z-[60] flex items-start justify-center overflow-y-auto bg-gray-950/40 p-4 sm:items-center" @click.self="createColumnModalOpen = false; $wire.closeCreateColumnModal()">
            <section role="dialog" aria-modal="true" aria-labelledby="new-column-title" class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-xl sm:p-7">
                <div class="mb-6 flex items-start justify-between gap-4">
                    <div>
                        <h2 id="new-column-title" class="text-lg font-semibold text-gray-900">Add a column</h2>
                        <p class="mt-1 text-sm text-gray-500">Name the new column for this board.</p>
                    </div>
                    <button type="button" wire:click="closeCreateColumnModal" @click="createColumnModalOpen = false" class="rounded-lg p-1 text-gray-400 transition hover:bg-gray-100 hover:text-gray-700" aria-label="Close dialog">×</button>
                </div>

                <form wire:submit="createColumn">
                    <div class="mb-6">
                        <label for="new-column-name" class="form-label">Column name</label>
                        <input id="new-column-name" type="text" wire:model="newColumnName" maxlength="255" required autofocus class="form-input" @error('newColumnName') aria-invalid="true" aria-describedby="new-column-name-error" @enderror>
                        @error('newColumnName')
                            <p id="new-column-name-error" class="form-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                        <button type="button" wire:click="closeCreateColumnModal" @click="createColumnModalOpen = false" class="button-secondary">Cancel</button>
                        <button type="submit" class="button-primary" wire:loading.attr="disabled" wire:target="createColumn">
                            <span wire:loading.remove wire:target="createColumn">Add column</span>
                            <span wire:loading wire:target="createColumn">Adding...</span>
                        </button>
                    </div>
                </form>
            </section>
        </div>
    @endif

    @if ($editingColumnId !== null)
        <div x-cloak x-show="editColumnModalOpen" x-transition.opacity class="fixed inset-0 z-[70] flex items-start justify-center overflow-y-auto bg-gray-950/40 p-4 sm:items-center" @click.self="editColumnModalOpen = false; $wire.closeEditColumnModal()">
            <section role="dialog" aria-modal="true" aria-labelledby="edit-column-title" class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-xl sm:p-7">
                <div class="mb-6 flex items-start justify-between gap-4">
                    <div>
                        <h2 id="edit-column-title" class="text-lg font-semibold text-gray-900">Edit column</h2>
                        <p class="mt-1 text-sm text-gray-500">Update the column name and accent color.</p>
                    </div>
                    <button type="button" wire:click="closeEditColumnModal" @click="editColumnModalOpen = false" class="rounded-lg p-1 text-gray-400 transition hover:bg-gray-100 hover:text-gray-700" aria-label="Close dialog">×</button>
                </div>

                <form wire:submit="updateColumn">
                    <div class="mb-5">
                        <label for="edit-column-name" class="form-label">Column name</label>
                        <input id="edit-column-name" type="text" wire:model="editColumnName" maxlength="255" required class="form-input" @error('editColumnName') aria-invalid="true" aria-describedby="edit-column-name-error" @enderror>
                        @error('editColumnName')
                            <p id="edit-column-name-error" class="form-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-6">
                        <label for="edit-column-color" class="form-label">Accent color</label>
                        <div class="flex items-center gap-3">
                            <input id="edit-column-color" type="color" wire:model="editColumnColor" class="size-12 cursor-pointer rounded-lg border border-gray-300 bg-white p-1 shadow-sm">
                            <p class="text-sm text-gray-500">This color marks the column beside its name.</p>
                        </div>
                        @error('editColumnColor')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                        <button type="button" wire:click="closeEditColumnModal" @click="editColumnModalOpen = false" class="button-secondary">Cancel</button>
                        <button type="submit" class="button-primary" wire:loading.attr="disabled" wire:target="updateColumn">
                            <span wire:loading.remove wire:target="updateColumn">Save column</span>
                            <span wire:loading wire:target="updateColumn">Saving...</span>
                        </button>
                    </div>
                </form>
            </section>
        </div>
    @endif

    @if ($deletingColumnId !== null)
        <div x-cloak x-show="deleteColumnModalOpen" x-transition.opacity class="fixed inset-0 z-[70] flex items-start justify-center overflow-y-auto bg-gray-950/50 p-4 sm:items-center" @click.self="deleteColumnModalOpen = false; deleteConfirmationInput = ''; $wire.closeDeleteColumnConfirmation()">
            <section role="alertdialog" aria-modal="true" aria-labelledby="delete-column-title" aria-describedby="delete-column-description" class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-xl sm:p-7">
                <div class="mb-5 flex items-start gap-4">
                    <div class="flex size-11 shrink-0 items-center justify-center rounded-full bg-red-100 text-red-700 dark:bg-red-950/60 dark:text-red-300">
                        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M10.3 3.9 2.8 17a2 2 0 0 0 1.7 3h15a2 2 0 0 0 1.7-3l-7.5-13.1a2 2 0 0 0-3.4 0Z" />
                        </svg>
                    </div>
                    <div class="min-w-0">
                        <h2 id="delete-column-title" class="text-lg font-semibold text-gray-900">Delete this column?</h2>
                        <p class="mt-1 text-sm text-gray-600">You are about to delete <span class="font-semibold text-gray-900" x-text="columnDeleteName"></span>.</p>
                    </div>
                </div>

                <div class="mb-5 rounded-xl border border-red-200 bg-red-50 p-4 dark:border-red-900/60 dark:bg-red-950/30">
                    <p id="delete-column-description" class="text-sm leading-6 text-red-800 dark:text-red-200" x-text="`This will permanently delete this column and ${columnDeleteTaskCount} ${columnDeleteTaskCount === 1 ? 'task' : 'tasks'} in it. This action cannot be undone.`"></p>
                </div>

                <form wire:submit="deleteColumn">
                    <div class="mb-6">
                        <label for="column-delete-confirmation" class="form-label">Type <span class="font-mono font-semibold">confirm</span> to continue</label>
                        <input id="column-delete-confirmation" type="text" wire:model="columnDeleteConfirmation" x-on:input="deleteConfirmationInput = $event.target.value" autocomplete="off" required class="form-input" @error('columnDeleteConfirmation') aria-invalid="true" aria-describedby="column-delete-confirmation-error" @enderror>
                        @error('columnDeleteConfirmation')
                            <p id="column-delete-confirmation-error" class="form-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                        <button type="button" wire:click="closeDeleteColumnConfirmation" @click="deleteColumnModalOpen = false; deleteConfirmationInput = ''" class="button-secondary">Cancel</button>
                        <button type="submit" x-bind:disabled="deleteConfirmationInput !== 'confirm'" class="inline-flex items-center justify-center gap-2 rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-red-700 focus:outline-none focus:ring-4 focus:ring-red-500/25 disabled:cursor-not-allowed disabled:opacity-50" wire:loading.attr="disabled" wire:target="deleteColumn">
                            <span wire:loading.remove wire:target="deleteColumn">Delete column</span>
                            <span wire:loading wire:target="deleteColumn">Deleting...</span>
                        </button>
                    </div>
                </form>
            </section>
        </div>
    @endif

    @if ($editingTaskId !== null)
        <div x-cloak x-show="taskEditModalOpen" x-transition.opacity class="fixed inset-0 z-[60] flex items-start justify-center overflow-y-auto bg-gray-950/40 p-4 sm:items-center" @click.self="taskEditModalOpen = false; $wire.closeTaskEditModal()">
            <section role="dialog" aria-modal="true" aria-labelledby="edit-task-title" class="max-h-[calc(100dvh-2rem)] w-full max-w-2xl overflow-y-auto rounded-2xl bg-white p-6 shadow-xl sm:p-7">
                <div class="mb-6 flex items-start justify-between gap-4">
                    <div>
                        <h2 id="edit-task-title" class="text-lg font-semibold text-gray-900">Edit task</h2>
                        <p class="mt-1 text-sm text-gray-500">Update the task title or description.</p>
                    </div>
                    <button type="button" wire:click="closeTaskEditModal" @click="taskEditModalOpen = false" class="rounded-lg p-1 text-gray-400 transition hover:bg-gray-100 hover:text-gray-700" aria-label="Close dialog">×</button>
                </div>

                <form wire:submit="updateTask">
                    <div class="mb-5">
                        <label for="edit-task-title-field" class="form-label">Title</label>
                        <input id="edit-task-title-field" type="text" wire:model="editTaskTitle" maxlength="255" required class="form-input" @error('editTaskTitle') aria-invalid="true" aria-describedby="edit-task-title-error" @enderror>
                        @error('editTaskTitle')
                            <p id="edit-task-title-error" class="form-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-6">
                        <p class="form-label">Description <span class="font-normal text-gray-400">(optional)</span></p>
                        <div
                            wire:key="task-description-editor-{{ $editingTaskId }}"
                            x-data="quillRichTextEditor({ placeholder: 'Add task details...', ariaLabel: 'Task description', minHeight: '12rem' })"
                            x-init="$nextTick(() => initialize())"
                        >
                            <div class="quill-rich-text-editor" wire:ignore>
                                <div x-ref="editor"></div>
                            </div>
                            <textarea x-ref="contentInput" wire:model="editTaskDescription" aria-hidden="true" tabindex="-1" class="hidden">{{ $editTaskDescription }}</textarea>
                            <textarea x-ref="initialHtml" aria-hidden="true" tabindex="-1" class="hidden">{{ $editTaskDescriptionHtml }}</textarea>
                        </div>
                        @error('editTaskDescription')
                            <p id="edit-task-description-error" class="form-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                        <button type="button" wire:click="closeTaskEditModal" @click="taskEditModalOpen = false" class="button-secondary">Cancel</button>
                        <button type="submit" class="button-primary" wire:loading.attr="disabled" wire:target="updateTask">
                            <span wire:loading.remove wire:target="updateTask">Save changes</span>
                            <span wire:loading wire:target="updateTask">Saving...</span>
                        </button>
                    </div>
                </form>
            </section>
        </div>
    @endif

    @script
        <script>
            const syncBoardCountsAndEmptyStates = () => {
                $wire.$el.querySelectorAll('[data-kanban-board-section]').forEach((board) => {
                    let boardTaskCount = 0;

                    board.querySelectorAll('[data-kanban-column]').forEach((column) => {
                        const taskCount = [...column.children].filter((child) => child.matches('.draggable')).length;
                        const emptyState = column.querySelector('[data-column-empty-state]');
                        const countBadge = column.closest('[data-kanban-column-wrapper]')?.querySelector('[data-column-task-count]');

                        if (emptyState) {
                            emptyState.classList.toggle('hidden', taskCount > 0);
                        }

                        if (countBadge) {
                            countBadge.textContent = String(taskCount);
                        }

                        boardTaskCount += taskCount;
                    });

                    const boardCount = board.querySelector('[data-board-task-count]');

                    if (boardCount) {
                        boardCount.textContent = `${boardTaskCount} ${boardTaskCount === 1 ? 'task' : 'tasks'}`;
                    }
                });
            };

            $wire.$el.addEventListener('card-dropped', (event) => {
                const payload = event.detail;
                const cardId = Number(payload.cardId);
                const toColumnId = Number(payload.toColumnId);
                const newPosition = Number(payload.newPosition);

                if (![cardId, toColumnId, newPosition].every(Number.isSafeInteger)) {
                    return;
                }

                syncBoardCountsAndEmptyStates();
                $wire.updateCardOrder(cardId, toColumnId, newPosition);
            });

            $wire.$el.addEventListener('click', (event) => {
                const clickedElement = event.target instanceof Element ? event.target : null;
                const deleteButton = clickedElement
                    ? clickedElement.closest('[data-task-delete-button]')
                    : null;

                if (deleteButton && $wire.$el.contains(deleteButton)) {
                    const taskId = Number(deleteButton.dataset.taskId);
                    const taskTitle = deleteButton.dataset.taskTitle || 'this task';

                    if (
                        Number.isSafeInteger(taskId)
                        && taskId > 0
                        && window.confirm(`Delete "${taskTitle}"? This action cannot be undone.`)
                    ) {
                        deleteButton.disabled = true;

                        $wire.deleteTask(taskId).catch(() => {
                            if (deleteButton.isConnected) {
                                deleteButton.disabled = false;
                            }
                        });
                    }

                    return;
                }

                const editButton = clickedElement
                    ? event.target.closest('[data-task-edit-button]')
                    : null;

                if (!editButton || !$wire.$el.contains(editButton)) {
                    return;
                }

                const taskId = Number(editButton.dataset.taskId);

                if (Number.isSafeInteger(taskId) && taskId > 0) {
                    $wire.openTaskEditModal(taskId);
                }
            });

            $wire.$el.addEventListener('task-updated', (event) => {
                const taskId = Number(event.detail.taskId);
                const card = [...$wire.$el.querySelectorAll('[data-item-id]')]
                    .find((item) => item.dataset.itemId === String(taskId));
                const title = card?.querySelector('[data-task-title]');

                if (!Number.isSafeInteger(taskId) || taskId < 1 || typeof event.detail.title !== 'string' || !title) {
                    return;
                }

                title.textContent = event.detail.title;
                title.closest('[data-task-edit-button]')?.setAttribute('aria-label', `Open task: ${event.detail.title}`);

                const deleteButton = card.querySelector('[data-task-delete-button]');

                if (deleteButton) {
                    deleteButton.dataset.taskTitle = event.detail.title;
                    deleteButton.setAttribute('aria-label', `Delete task: ${event.detail.title}`);
                }
            });

            $wire.$el.addEventListener('task-deleted', (event) => {
                const taskId = Number(event.detail.taskId);
                const card = [...$wire.$el.querySelectorAll('[data-item-id]')]
                    .find((item) => item.dataset.itemId === String(taskId));

                if (!Number.isSafeInteger(taskId) || taskId < 1 || !card) {
                    return;
                }

                card.remove();
                syncBoardCountsAndEmptyStates();
            });

            $wire.$el.addEventListener('column-reordered', (event) => {
                const payload = event.detail;

                if (!payload || typeof payload !== 'object') {
                    return;
                }

                const columnId = Number(payload.columnId);
                const newPosition = Number(payload.newPosition);

                if (!Number.isSafeInteger(columnId) || !Number.isSafeInteger(newPosition) || columnId < 1 || newPosition < 0) {
                    return;
                }

                const boardSection = event.target.closest('[data-kanban-board-section]');
                const boardId = boardSection?.dataset.boardId;

                const findColumnsContainer = () => [...$wire.$el.querySelectorAll('[data-kanban-columns]')]
                    .find((container) => container.closest('[data-kanban-board-section]')?.dataset.boardId === boardId);

                const applyOrder = (orderedColumnIds) => {
                    const container = findColumnsContainer();

                    if (!container || !Array.isArray(orderedColumnIds)) {
                        return;
                    }

                    orderedColumnIds.forEach((orderedColumnId) => {
                        const column = [...container.children].find((child) =>
                            child.matches('[data-kanban-column-wrapper]')
                            && child.dataset.columnId === String(orderedColumnId),
                        );

                        if (column) {
                            container.append(column);
                        }
                    });
                };

                $wire.updateColumnOrder(columnId, newPosition)
                    .then((orderedColumnIds) => applyOrder(orderedColumnIds))
                    .catch(() => applyOrder(payload.originalColumnIds));
            });

            $wire.$el.addEventListener('task-created', (event) => {
                const { columnId, task } = event.detail;
                const column = [...$wire.$el.querySelectorAll('[data-kanban-column]')]
                    .find((dropzone) => String(dropzone.dataset.columnId) === String(columnId));

                if (!column || !task) {
                    return;
                }

                const card = document.createElement('article');
                card.className = 'draggable relative cursor-grab rounded-lg border border-gray-200 bg-white p-4 shadow-sm transition hover:border-indigo-200 hover:shadow-md active:cursor-grabbing';
                card.dataset.itemId = String(task.id);

                const editButton = document.createElement('button');
                editButton.type = 'button';
                editButton.dataset.taskEditButton = '';
                editButton.dataset.taskId = String(task.id);
                editButton.setAttribute('aria-label', `Open task: ${task.title}`);
                editButton.className = 'block w-full rounded pr-10 text-left focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500';

                const title = document.createElement('span');
                title.dataset.taskTitle = '';
                title.className = 'block text-sm font-medium leading-5 text-gray-800';
                title.textContent = task.title;
                editButton.append(title);

                const createdAt = document.createElement('time');
                createdAt.dataset.taskCreatedAt = '';
                createdAt.dateTime = task.createdAt;
                createdAt.className = 'mt-2 block text-xs text-gray-500';
                createdAt.textContent = `Created ${task.createdAtLabel}`;
                editButton.append(createdAt);

                const deleteButton = document.createElement('button');
                deleteButton.type = 'button';
                deleteButton.dataset.taskDeleteButton = '';
                deleteButton.dataset.taskId = String(task.id);
                deleteButton.dataset.taskTitle = task.title;
                deleteButton.draggable = false;
                deleteButton.setAttribute('aria-label', `Delete task: ${task.title}`);
                deleteButton.title = 'Delete task';
                deleteButton.className = 'absolute right-2 top-2 inline-flex size-8 shrink-0 items-center justify-center rounded-lg bg-red-50 text-red-600 transition hover:bg-red-100 focus:outline-none focus:ring-2 focus:ring-red-500 dark:bg-red-950/40 dark:text-red-400 dark:hover:bg-red-950/70';
                deleteButton.innerHTML = '<svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M10 11v6m4-6v6M6 7l1 13h10l1-13M9 7V4h6v3" /></svg>';

                card.append(editButton, deleteButton);

                const emptyState = column.querySelector('[data-column-empty-state]');

                if (emptyState) {
                    column.insertBefore(card, emptyState);
                } else {
                    column.append(card);
                }

                syncBoardCountsAndEmptyStates();
            });
        </script>
    @endscript
</div>
