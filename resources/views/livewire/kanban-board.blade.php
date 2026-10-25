<div data-kanban-board>
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
            <section wire:key="kanban-board-{{ $board->id }}" data-kanban-board-section aria-labelledby="board-{{ $board->id }}-title">
                <div class="mb-4 flex items-center justify-between">
                    <h2 id="board-{{ $board->id }}-title" class="text-base font-semibold text-gray-900">{{ $board->name }}</h2>
                    <span class="text-sm text-gray-500" data-board-task-count>{{ $board->columns->sum(fn ($column) => $column->tasks->count()) }} tasks</span>
                </div>

                <div class="grid grid-cols-1 items-start gap-5 sm:grid-cols-2 xl:grid-cols-4">
                    @foreach ($board->columns as $column)
                        <section wire:key="kanban-column-{{ $column->id }}" data-kanban-column-wrapper class="min-w-0 rounded-xl border border-gray-200 bg-gray-100/70 p-3.5" aria-labelledby="column-{{ $column->id }}-title">
                            <div class="mb-3 flex items-center justify-between px-1">
                                <div class="flex min-w-0 items-center gap-2.5">
                                    <span class="size-2 shrink-0 rounded-full bg-indigo-500"></span>
                                    <h3 id="column-{{ $column->id }}-title" class="truncate text-sm font-semibold text-gray-800">{{ $column->name }}</h3>
                                    <span class="rounded-md bg-white px-1.5 py-0.5 text-xs font-medium text-gray-500 ring-1 ring-inset ring-gray-200" data-column-task-count>{{ $column->tasks->count() }}</span>
                                </div>
                            </div>

                            <div wire:ignore wire:key="kanban-column-dropzone-{{ $column->id }}" class="min-h-24 space-y-3 rounded-lg" data-kanban-column data-column-id="{{ $column->id }}">
                                @foreach ($column->tasks as $task)
                                    <article class="draggable cursor-grab rounded-lg border border-gray-200 bg-white p-4 shadow-sm transition hover:border-indigo-200 hover:shadow-md active:cursor-grabbing" data-item-id="{{ $task->id }}">
                                        <p class="text-sm font-medium leading-5 text-gray-800">{{ $task->title }}</p>
                                        @if ($task->description)
                                            <p class="mt-2 line-clamp-3 text-sm leading-5 text-gray-500">{{ $task->description }}</p>
                                        @endif
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

                const title = document.createElement('p');
                title.className = 'text-sm font-medium leading-5 text-gray-800';
                title.textContent = task.title;
                card.append(title);

                if (task.description) {
                    const description = document.createElement('p');
                    description.className = 'mt-2 line-clamp-3 text-sm leading-5 text-gray-500';
                    description.textContent = task.description;
                    card.append(description);
                }

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
