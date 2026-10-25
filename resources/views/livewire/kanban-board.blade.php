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

    <div class="space-y-10" wire:ignore>
        @forelse ($project->boards as $board)
            <section data-kanban-board-section aria-labelledby="board-{{ $board->id }}-title">
                <div class="mb-4 flex items-center justify-between">
                    <h2 id="board-{{ $board->id }}-title" class="text-base font-semibold text-gray-900">{{ $board->name }}</h2>
                    <span class="text-sm text-gray-500" data-board-task-count>{{ $board->columns->sum(fn ($column) => $column->tasks->count()) }} tasks</span>
                </div>

                <div class="grid grid-cols-1 items-start gap-5 sm:grid-cols-2 xl:grid-cols-4">
                    @foreach ($board->columns as $column)
                        <section data-kanban-column-wrapper class="min-w-0 rounded-xl border border-gray-200 bg-gray-100/70 p-3.5" aria-labelledby="column-{{ $column->id }}-title">
                            <div class="mb-3 flex items-center justify-between px-1">
                                <div class="flex min-w-0 items-center gap-2.5">
                                    <span class="size-2 shrink-0 rounded-full bg-indigo-500"></span>
                                    <h3 id="column-{{ $column->id }}-title" class="truncate text-sm font-semibold text-gray-800">{{ $column->name }}</h3>
                                    <span class="rounded-md bg-white px-1.5 py-0.5 text-xs font-medium text-gray-500 ring-1 ring-inset ring-gray-200" data-column-task-count>{{ $column->tasks->count() }}</span>
                                </div>
                            </div>

                            <div class="min-h-24 space-y-3 rounded-lg" data-kanban-column data-column-id="{{ $column->id }}">
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

                syncBoardCountsAndEmptyStates();
                $wire.updateCardOrder(payload.cardId, payload.toColumnId, payload.newPosition);
            });
        </script>
    @endscript
</div>
