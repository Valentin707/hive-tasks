<div data-kanban-board x-data="{ taskEditModalOpen: false }" x-on:task-edit-opened.window="taskEditModalOpen = true" x-on:task-updated.window="taskEditModalOpen = false" x-on:keydown.escape.window="if (taskEditModalOpen) { taskEditModalOpen = false; $wire.closeTaskEditModal() }">
    <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="mb-2 text-sm font-medium text-indigo-600">Your workspace</p>
            <h1 class="text-2xl font-semibold tracking-tight text-gray-900 sm:text-3xl">{{ $project->name }}</h1>
            @if ($project->description)
                <p class="mt-2 text-sm text-gray-500">{{ $project->description }}</p>
            @endif
        </div>
    </div>

    <div class="space-y-10">
        @forelse ($project->boards as $board)
            <section wire:key="kanban-board-{{ $board->id }}" data-kanban-board-section data-board-id="{{ $board->id }}" x-data="{ reorderMode: false }" x-bind:data-column-reorder-mode="reorderMode ? 'true' : 'false'" x-on:column-reorder-start="reorderMode = true" x-on:column-reorder-end="reorderMode = false" aria-labelledby="board-{{ $board->id }}-title">
                <div class="mb-4 flex items-center justify-between">
                    <h2 id="board-{{ $board->id }}-title" class="text-base font-semibold text-gray-900">{{ $board->name }}</h2>
                    <div class="flex items-center gap-3">
                        <p x-cloak x-show="reorderMode" class="text-xs text-gray-500">Release the handle to finish</p>
                        <span class="text-sm text-gray-500" data-board-task-count>{{ $board->columns->sum(fn ($column) => $column->tasks->count()) }} tasks</span>
                    </div>
                </div>

                <div class="grid grid-cols-1 items-start gap-5 sm:grid-cols-2 xl:grid-cols-4" data-kanban-columns>
                    @foreach ($board->columns as $column)
                        <section wire:key="kanban-column-{{ $column->id }}" data-kanban-column-wrapper data-column-id="{{ $column->id }}" class="min-w-0 rounded-xl border border-gray-200 bg-gray-100/70 p-3.5 shadow-sm transition duration-150" x-bind:class="reorderMode ? 'border-indigo-400 ring-2 ring-indigo-100 shadow-md' : ''" aria-labelledby="column-{{ $column->id }}-title">
                            <div class="mb-3 flex items-center justify-between gap-2 px-1">
                                <div class="flex min-w-0 items-center gap-2.5">
                                    <span class="size-2 shrink-0 rounded-full bg-indigo-500"></span>
                                    <h3 id="column-{{ $column->id }}-title" class="truncate text-sm font-semibold text-gray-800">{{ $column->name }}</h3>
                                    <span class="rounded-md bg-white px-1.5 py-0.5 text-xs font-medium text-gray-500 ring-1 ring-inset ring-gray-200" data-column-task-count>{{ $column->tasks->count() }}</span>
                                </div>
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
                            </div>

                            <div wire:ignore wire:key="kanban-column-dropzone-{{ $column->id }}" class="min-h-24 space-y-3 rounded-lg" data-kanban-column data-column-id="{{ $column->id }}">
                                @foreach ($column->tasks as $task)
                                    <article class="draggable cursor-grab rounded-lg border border-gray-200 bg-white p-4 shadow-sm transition hover:border-indigo-200 hover:shadow-md active:cursor-grabbing" data-item-id="{{ $task->id }}">
                                        <button type="button" data-task-edit-button data-task-id="{{ $task->id }}" aria-label="Open task: {{ $task->title }}" class="block w-full rounded text-left focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                            <span data-task-title class="block text-sm font-medium leading-5 text-gray-800">{{ $task->title }}</span>
                                            <time datetime="{{ $task->created_at->toIso8601String() }}" data-task-created-at class="mt-2 block text-xs text-gray-500">Created {{ $task->created_at->format('M j, Y') }}</time>
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

                                <div x-cloak x-show="open" x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center bg-gray-950/40 p-4" @click.self="open = false">
                                    <section role="dialog" aria-modal="true" aria-labelledby="new-task-title-{{ $column->id }}" class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-xl sm:p-7">
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
                                                <label for="task-description-{{ $column->id }}" class="form-label">Description <span class="font-normal text-gray-400">(optional)</span></label>
                                                <textarea id="task-description-{{ $column->id }}" wire:model="taskDescription" rows="4" maxlength="10000" class="form-input resize-y" @error('taskDescription') aria-invalid="true" aria-describedby="task-description-error-{{ $column->id }}" @enderror></textarea>
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

    @if ($editingTaskId !== null)
        <div x-cloak x-show="taskEditModalOpen" x-transition.opacity class="fixed inset-0 z-[60] flex items-center justify-center bg-gray-950/40 p-4" @click.self="taskEditModalOpen = false; $wire.closeTaskEditModal()">
            <section role="dialog" aria-modal="true" aria-labelledby="edit-task-title" class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-xl sm:p-7">
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
                        <label for="edit-task-description" class="form-label">Description <span class="font-normal text-gray-400">(optional)</span></label>
                        <textarea id="edit-task-description" wire:model="editTaskDescription" rows="5" maxlength="10000" class="form-input resize-y" @error('editTaskDescription') aria-invalid="true" aria-describedby="edit-task-description-error" @enderror></textarea>
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
                const editButton = event.target instanceof Element
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
                card.className = 'draggable cursor-grab rounded-lg border border-gray-200 bg-white p-4 shadow-sm transition hover:border-indigo-200 hover:shadow-md active:cursor-grabbing';
                card.dataset.itemId = String(task.id);

                const editButton = document.createElement('button');
                editButton.type = 'button';
                editButton.dataset.taskEditButton = '';
                editButton.dataset.taskId = String(task.id);
                editButton.setAttribute('aria-label', `Open task: ${task.title}`);
                editButton.className = 'block w-full rounded text-left focus:outline-none focus:ring-2 focus:ring-indigo-500';

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

                card.append(editButton);

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
