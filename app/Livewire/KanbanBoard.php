<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Column;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

class KanbanBoard extends Component
{
    #[Locked]
    public int $projectId;

    #[Locked]
    public ?int $boardId = null;

    #[Locked]
    public ?int $taskColumnId = null;

    #[Locked]
    public ?int $editingTaskId = null;

    public string $taskTitle = '';

    public string $taskDescription = '';

    public string $editTaskTitle = '';

    public string $editTaskDescription = '';

    public function mount(int $projectId, ?int $boardId = null): void
    {
        $this->projectId = $projectId;
        $this->boardId = $boardId;
    }

    public function openTaskModal(int $columnId): void
    {
        $this->activeColumns()->whereKey($columnId)->firstOrFail();

        $this->taskColumnId = $columnId;
        $this->reset(['taskTitle', 'taskDescription']);
        $this->resetValidation();
        $this->dispatch('task-create-opened', columnId: $columnId);
    }

    public function createTask(): void
    {
        $validated = $this->validate([
            'taskColumnId' => ['required', 'integer', 'min:1'],
            'taskTitle' => ['required', 'string', 'max:255'],
            'taskDescription' => ['nullable', 'string', 'max:10000'],
        ]);

        $columnId = (int) $validated['taskColumnId'];

        $task = DB::transaction(function () use ($columnId, $validated): Task {
            $column = $this->activeColumns()
                ->whereKey($columnId)
                ->lockForUpdate()
                ->firstOrFail();

            $position = (int) $column->tasks()->max('position') + 1;

            return $column->tasks()->create([
                'title' => $validated['taskTitle'],
                'description' => RichTextSanitizer::sanitize($validated['taskDescription'] ?? '') ?: null,
                'position' => $position,
            ]);
        }, 3);

        $this->reset(['taskTitle', 'taskDescription']);
        $this->taskColumnId = null;
        $this->resetValidation();

        $this->dispatch('task-created', columnId: $columnId, task: [
            'id' => (int) $task->getKey(),
            'title' => $task->title,
            'createdAt' => $task->created_at?->toIso8601String(),
            'createdAtLabel' => $task->created_at?->format('M j, Y'),
        ]);
    }

    public function openTaskEditModal(int $taskId): void
    {
        abort_if($taskId < 1, 422);

        $task = $this->activeTasks()->whereKey($taskId)->firstOrFail();

        $this->editingTaskId = (int) $task->getKey();
        $this->editTaskTitle = $task->title;
        $this->editTaskDescription = $task->description ?? '';
        $this->resetValidation();
        $this->dispatch('task-edit-opened');
    }

    public function updateTask(): void
    {
        abort_if($this->editingTaskId === null, 404);

        $validated = $this->validate([
            'editTaskTitle' => ['required', 'string', 'max:255'],
            'editTaskDescription' => ['nullable', 'string', 'max:10000'],
        ]);

        $task = $this->activeTasks()->whereKey($this->editingTaskId)->firstOrFail();
        $task->title = $validated['editTaskTitle'];
        $task->description = RichTextSanitizer::sanitize($validated['editTaskDescription'] ?? '') ?: null;
        $task->save();

        $taskId = (int) $task->getKey();
        $title = $task->title;

        $this->closeTaskEditModal();
        $this->dispatch('task-updated', taskId: $taskId, title: $title);
    }

    public function closeTaskEditModal(): void
    {
        $this->editingTaskId = null;
        $this->reset(['editTaskTitle', 'editTaskDescription']);
        $this->resetValidation();
    }

    public function updateColumnOrder(int $columnId, int $newPositionIndex): array
    {
        abort_if($columnId < 1 || $newPositionIndex < 0, 422);

        return DB::transaction(function () use ($columnId, $newPositionIndex): array {
            $boardId = $this->activeColumns()
                ->whereKey($columnId)
                ->value('board_id');

            abort_if($boardId === null, 404);

            $columns = $this->activeColumns()
                ->where('board_id', $boardId)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            $targetColumn = $columns->first(
                fn (Column $column): bool => (int) $column->getKey() === $columnId,
            );

            abort_if($targetColumn === null, 404);

            $orderedColumns = $columns
                ->sort(fn (Column $left, Column $right): int => [
                    (int) $left->position,
                    (int) $left->getKey(),
                ] <=> [
                    (int) $right->position,
                    (int) $right->getKey(),
                ])
                ->reject(fn (Column $column): bool => (int) $column->getKey() === $columnId)
                ->values();

            $orderedColumns->splice(
                min($newPositionIndex, $orderedColumns->count()),
                0,
                [$targetColumn],
            );

            foreach ($orderedColumns as $positionIndex => $column) {
                $position = $positionIndex + 1;

                if ((int) $column->position === $position) {
                    continue;
                }

                $column->position = $position;
                $column->save();
            }

            return $orderedColumns
                ->map(static fn (Column $column): int => (int) $column->getKey())
                ->all();
        }, 3);
    }

    public function updateCardOrder(int $cardId, int $toColumnId, int $newPosition): void
    {
        abort_if($cardId < 1 || $toColumnId < 1 || $newPosition < 0, 422);

        DB::transaction(function () use ($cardId, $toColumnId, $newPosition): void {
            $task = Task::query()
                ->whereKey($cardId)
                ->whereHas('column.board', fn (Builder $query) => $query
                    ->where('project_id', $this->projectId)
                    ->when($this->boardId !== null, fn (Builder $query) => $query->whereKey($this->boardId)))
                ->lockForUpdate()
                ->firstOrFail();

            $targetColumn = Column::query()
                ->whereKey($toColumnId)
                ->whereHas('board', fn (Builder $query) => $query
                    ->where('project_id', $this->projectId)
                    ->when($this->boardId !== null, fn (Builder $query) => $query->whereKey($this->boardId)))
                ->firstOrFail();

            $sourceColumnId = (int) $task->column_id;
            $targetColumnId = (int) $targetColumn->getKey();

            Column::query()
                ->whereKey(collect([$sourceColumnId, $targetColumnId])->unique()->sort()->values())
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            $destinationTasks = Task::query()
                ->where('column_id', $targetColumnId)
                ->orderBy('position')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            $orderedDestinationIds = $destinationTasks
                ->reject(fn (Task $destinationTask): bool => (int) $destinationTask->getKey() === $cardId)
                ->modelKeys();

            $insertionPosition = min($newPosition, count($orderedDestinationIds));
            array_splice($orderedDestinationIds, $insertionPosition, 0, [$cardId]);

            $tasksById = $destinationTasks->keyBy(fn (Task $destinationTask): int => (int) $destinationTask->getKey());
            $tasksById->put($cardId, $task);

            foreach ($orderedDestinationIds as $position => $orderedTaskId) {
                $orderedTask = $tasksById->get((int) $orderedTaskId);
                $orderedTask->column_id = $targetColumnId;
                $orderedTask->position = $position + 1;
                $orderedTask->save();
            }

            if ($sourceColumnId !== $targetColumnId) {
                $sourceTasks = Task::query()
                    ->where('column_id', $sourceColumnId)
                    ->where('id', '!=', $cardId)
                    ->orderBy('position')
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get();

                foreach ($sourceTasks as $position => $sourceTask) {
                    $sourceTask->position = $position + 1;
                    $sourceTask->save();
                }
            }
        }, 3);
    }

    public function render(): View
    {
        $boardsQuery = fn (HasMany $query) => $query
            ->when($this->boardId !== null, fn (Builder $query) => $query->whereKey($this->boardId))
            ->orderBy('id');

        $project = Project::query()
            ->with([
                'boards' => $boardsQuery,
                'boards.columns' => fn (HasMany $query) => $query->orderBy('position')->orderBy('id'),
                'boards.columns.tasks' => fn (HasMany $query) => $query->orderBy('position')->orderBy('id'),
            ])
            ->findOrFail($this->projectId);

        abort_if($this->boardId !== null && $project->boards->isEmpty(), 404);

        $taskDescriptionHtml = RichTextSanitizer::sanitize($this->taskDescription);
        $editTaskDescriptionHtml = RichTextSanitizer::sanitize($this->editTaskDescription);

        return view('livewire.kanban-board', compact('project', 'taskDescriptionHtml', 'editTaskDescriptionHtml'));
    }

    private function activeColumns(): Builder
    {
        return Column::query()->whereHas('board', fn (Builder $query) => $query
            ->where('project_id', $this->projectId)
            ->when($this->boardId !== null, fn (Builder $query) => $query->whereKey($this->boardId)));
    }

    private function activeTasks(): Builder
    {
        return Task::query()->whereHas('column.board', fn (Builder $query) => $query
            ->where('project_id', $this->projectId)
            ->when($this->boardId !== null, fn (Builder $query) => $query->whereKey($this->boardId)));
    }
}
