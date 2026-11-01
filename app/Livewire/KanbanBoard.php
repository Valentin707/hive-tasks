<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Board;
use App\Models\ChecklistItem;
use App\Models\Column;
use App\Models\Label;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
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

    #[Locked]
    public ?int $newColumnBoardId = null;

    #[Locked]
    public ?int $editingColumnId = null;

    #[Locked]
    public ?int $deletingColumnId = null;

    public string $taskTitle = '';

    public string $taskDescription = '';

    /** @var list<int|string> */
    public array $taskLabelIds = [];

    public string $newTaskLabelName = '';

    public string $newTaskLabelColor = '#6366f1';

    public string $search = '';

    public string $editTaskTitle = '';

    public string $editTaskDescription = '';

    /** @var list<int|string> */
    public array $editTaskLabelIds = [];

    public string $newEditTaskLabelName = '';

    public string $newEditTaskLabelColor = '#6366f1';

    /** @var array<int, string> */
    public array $checklistItemTitles = [];

    public string $newColumnName = '';

    public string $editColumnName = '';

    public string $editColumnColor = '#6366f1';

    public string $columnDeleteConfirmation = '';

    public function mount(int $projectId, ?int $boardId = null): void
    {
        $this->projectId = $projectId;
        $this->boardId = $boardId;
    }

    /**
     * @return EloquentCollection<int, Label>
     */
    #[Computed]
    public function userLabels(): EloquentCollection
    {
        return Label::query()->orderBy('name')->get();
    }

    public function openTaskModal(int $columnId): void
    {
        $this->activeColumns()->whereKey($columnId)->firstOrFail();

        $this->taskColumnId = $columnId;
        $this->reset(['taskTitle', 'taskDescription', 'taskLabelIds', 'newTaskLabelName', 'newTaskLabelColor']);
        $this->resetValidation();
        $this->dispatch('task-create-opened', columnId: $columnId);
    }

    public function createTask(): void
    {
        $validated = $this->validate([
            'taskColumnId' => ['required', 'integer', 'min:1'],
            'taskTitle' => ['required', 'string', 'max:255'],
            'taskDescription' => ['nullable', 'string', 'max:10000'],
            'taskLabelIds' => ['array'],
            'taskLabelIds.*' => ['integer', 'min:1', 'distinct'],
        ]);

        $columnId = (int) $validated['taskColumnId'];
        $labelIds = $this->validatedUserLabelIds($validated['taskLabelIds'] ?? [], 'taskLabelIds');

        $task = DB::transaction(function () use ($columnId, $labelIds, $validated): Task {
            $column = $this->activeColumns()
                ->whereKey($columnId)
                ->lockForUpdate()
                ->firstOrFail();

            $position = (int) $column->tasks()->max('position') + 1;

            $task = $column->tasks()->create([
                'title' => $validated['taskTitle'],
                'description' => RichTextSanitizer::sanitize($validated['taskDescription'] ?? '') ?: null,
                'position' => $position,
            ]);

            $task->labels()->sync($labelIds);

            return $task;
        }, 3);

        $this->reset(['taskTitle', 'taskDescription', 'taskLabelIds', 'newTaskLabelName', 'newTaskLabelColor']);
        $this->taskColumnId = null;
        $this->resetValidation();

        $search = mb_strtolower(mb_substr(trim($this->search), 0, 100));

        $this->dispatch('task-created', columnId: $columnId, task: [
            'id' => (int) $task->getKey(),
            'title' => $task->title,
            'createdAt' => $task->created_at?->toIso8601String(),
            'createdAtLabel' => $task->created_at?->format('M j, Y'),
            'visibleInCurrentSearch' => $search === '' || str_contains(mb_strtolower($task->title), $search),
        ]);
    }

    public function addLabelToNewTask(): void
    {
        abort_if($this->taskColumnId === null, 404);
        $this->activeColumns()->whereKey($this->taskColumnId)->firstOrFail();

        $validated = $this->validate([
            'taskLabelIds' => ['array'],
            'taskLabelIds.*' => ['integer', 'min:1', 'distinct'],
            'newTaskLabelName' => ['required', 'string', 'max:80'],
            'newTaskLabelColor' => ['required', 'string', 'regex:/\A#[0-9a-fA-F]{6}\z/'],
        ]);

        $this->taskLabelIds = $this->validatedUserLabelIds($validated['taskLabelIds'] ?? [], 'taskLabelIds');
        $label = $this->createOrGetUserLabel($validated['newTaskLabelName'], $validated['newTaskLabelColor']);
        $this->taskLabelIds = array_values(array_unique([...$this->taskLabelIds, (int) $label->getKey()]));
        $this->newTaskLabelName = '';
        $this->resetValidation();
        unset($this->userLabels);
    }

    public function openTaskEditModal(int $taskId): void
    {
        abort_if($taskId < 1, 422);

        $task = $this->activeTasks()->with(['labels', 'checklists'])->whereKey($taskId)->firstOrFail();

        $this->editingTaskId = (int) $task->getKey();
        $this->editTaskTitle = $task->title;
        $this->editTaskDescription = $task->description ?? '';
        $this->editTaskLabelIds = $task->labels
            ->map(static fn (Label $label): int => (int) $label->getKey())
            ->all();
        $this->checklistItemTitles = $task->checklists
            ->mapWithKeys(static fn (ChecklistItem $item): array => [(int) $item->getKey() => $item->title])
            ->all();
        $this->newEditTaskLabelName = '';
        $this->newEditTaskLabelColor = '#6366f1';
        $this->resetValidation();
        $this->dispatch('task-edit-opened');
    }

    public function updateTask(): void
    {
        abort_if($this->editingTaskId === null, 404);

        $validated = $this->validate([
            'editTaskTitle' => ['required', 'string', 'max:255'],
            'editTaskDescription' => ['nullable', 'string', 'max:10000'],
            'editTaskLabelIds' => ['array'],
            'editTaskLabelIds.*' => ['integer', 'min:1', 'distinct'],
        ]);

        $labelIds = $this->validatedUserLabelIds($validated['editTaskLabelIds'] ?? [], 'editTaskLabelIds');
        $editingTaskId = $this->editingTaskId;

        $task = DB::transaction(function () use ($editingTaskId, $labelIds, $validated): Task {
            $task = $this->activeTasks()->whereKey($editingTaskId)->lockForUpdate()->firstOrFail();
            $task->title = $validated['editTaskTitle'];
            $task->description = RichTextSanitizer::sanitize($validated['editTaskDescription'] ?? '') ?: null;
            $task->save();
            $task->labels()->sync($labelIds);

            return $task;
        }, 3);

        $taskId = (int) $task->getKey();
        $title = $task->title;

        $this->closeTaskEditModal();
        $this->dispatch('task-updated', taskId: $taskId, title: $title);
    }

    public function addLabelToEditedTask(): void
    {
        abort_if($this->editingTaskId === null, 404);
        $this->activeTasks()->whereKey($this->editingTaskId)->firstOrFail();

        $validated = $this->validate([
            'editTaskLabelIds' => ['array'],
            'editTaskLabelIds.*' => ['integer', 'min:1', 'distinct'],
            'newEditTaskLabelName' => ['required', 'string', 'max:80'],
            'newEditTaskLabelColor' => ['required', 'string', 'regex:/\A#[0-9a-fA-F]{6}\z/'],
        ]);

        $this->editTaskLabelIds = $this->validatedUserLabelIds($validated['editTaskLabelIds'] ?? [], 'editTaskLabelIds');
        $label = $this->createOrGetUserLabel($validated['newEditTaskLabelName'], $validated['newEditTaskLabelColor']);
        $this->editTaskLabelIds = array_values(array_unique([...$this->editTaskLabelIds, (int) $label->getKey()]));
        $this->newEditTaskLabelName = '';
        $this->resetValidation();
        unset($this->userLabels);
    }

    public function addChecklistItem(int $taskId): void
    {
        abort_if($taskId < 1, 422);

        $item = DB::transaction(function () use ($taskId): ChecklistItem {
            $task = $this->activeTasks()->whereKey($taskId)->lockForUpdate()->firstOrFail();
            $lastPosition = $task->checklists()->max('position');

            return $task->checklists()->create([
                'title' => 'New item...',
                'is_completed' => false,
                'position' => $lastPosition === null ? 0 : (int) $lastPosition + 1,
            ]);
        }, 3);

        $this->checklistItemTitles[(int) $item->getKey()] = $item->title;
    }

    public function toggleChecklistState(int $itemId): void
    {
        abort_if($itemId < 1, 422);

        DB::transaction(function () use ($itemId): void {
            $item = $this->activeChecklistItems()->whereKey($itemId)->lockForUpdate()->firstOrFail();
            $item->is_completed = ! $item->is_completed;
            $item->save();
        }, 3);
    }

    public function updateChecklistItemTitle(int $itemId, string $newTitle): void
    {
        abort_if($itemId < 1, 422);

        $item = $this->activeChecklistItems()->whereKey($itemId)->firstOrFail();
        $attribute = "checklistItemTitles.{$itemId}";
        $validatedTitle = Validator::make(
            ['checklistItemTitles' => [$itemId => $newTitle]],
            [$attribute => ['string', 'max:255']],
        )->validate()['checklistItemTitles'][$itemId];

        $item->title = $validatedTitle;
        $item->save();
        $this->checklistItemTitles[$itemId] = $validatedTitle;
    }

    public function updatedChecklistItemTitles(mixed $value, string|int|null $key = null): void
    {
        abort_if($key === null || ! ctype_digit((string) $key) || ! is_string($value), 422);

        $this->updateChecklistItemTitle((int) $key, $value);
    }

    public function deleteChecklistItem(int $itemId): void
    {
        abort_if($itemId < 1, 422);

        $this->activeChecklistItems()->whereKey($itemId)->firstOrFail()->delete();
        unset($this->checklistItemTitles[$itemId]);
    }

    public function deleteTask(int $taskId): void
    {
        abort_if($taskId < 1, 422);

        $task = $this->activeTasks()->whereKey($taskId)->firstOrFail();
        $columnId = (int) $task->column_id;
        $task->delete();

        $this->dispatch('task-deleted', taskId: $taskId, columnId: $columnId);
    }

    public function closeTaskEditModal(): void
    {
        $this->editingTaskId = null;
        $this->reset([
            'editTaskTitle',
            'editTaskDescription',
            'editTaskLabelIds',
            'newEditTaskLabelName',
            'newEditTaskLabelColor',
            'checklistItemTitles',
        ]);
        $this->resetValidation();
    }

    public function openCreateColumnModal(int $boardId): void
    {
        abort_if($boardId < 1, 422);

        $this->activeBoards()->whereKey($boardId)->firstOrFail();

        $this->newColumnBoardId = $boardId;
        $this->newColumnName = '';
        $this->resetValidation();
        $this->dispatch('column-create-opened');
    }

    public function createColumn(): void
    {
        abort_if($this->newColumnBoardId === null, 404);

        $validated = $this->validate([
            'newColumnName' => ['required', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($validated): void {
            $board = $this->activeBoards()
                ->whereKey($this->newColumnBoardId)
                ->lockForUpdate()
                ->firstOrFail();

            $position = (int) $board->columns()->max('position') + 1;

            $board->columns()->create([
                'name' => $validated['newColumnName'],
                'position' => $position,
                'color' => '#6366f1',
            ]);
        }, 3);

        $this->newColumnBoardId = null;
        $this->newColumnName = '';
        $this->resetValidation();
        $this->dispatch('column-created');
    }

    public function closeCreateColumnModal(): void
    {
        $this->newColumnBoardId = null;
        $this->newColumnName = '';
        $this->resetValidation();
    }

    public function openEditColumnModal(int $columnId): void
    {
        abort_if($columnId < 1, 422);

        $column = $this->activeColumns()->whereKey($columnId)->firstOrFail();

        $this->editingColumnId = (int) $column->getKey();
        $this->editColumnName = $column->name;
        $this->editColumnColor = $column->color;
        $this->resetValidation();
        $this->dispatch('column-edit-opened');
    }

    public function updateColumn(): void
    {
        abort_if($this->editingColumnId === null, 404);

        $validated = $this->validate([
            'editColumnName' => ['required', 'string', 'max:255'],
            'editColumnColor' => ['required', 'string', 'regex:/\A#[0-9a-fA-F]{6}\z/'],
        ], ['editColumnColor.regex' => 'Choose a valid accent color.'], ['editColumnColor' => 'accent color']);

        $column = $this->activeColumns()->whereKey($this->editingColumnId)->firstOrFail();
        $column->name = $validated['editColumnName'];
        $column->color = $validated['editColumnColor'];
        $column->save();

        $columnId = (int) $column->getKey();
        $columnName = $column->name;
        $columnColor = $column->color;

        $this->closeEditColumnModal();
        $this->dispatch('column-updated', columnId: $columnId, columnName: $columnName, columnColor: $columnColor);
    }

    public function closeEditColumnModal(): void
    {
        $this->editingColumnId = null;
        $this->reset(['editColumnName']);
        $this->editColumnColor = '#6366f1';
        $this->resetValidation();
    }

    public function openDeleteColumnConfirmation(int $columnId): void
    {
        abort_if($columnId < 1, 422);

        $column = $this->activeColumns()
            ->withCount('tasks')
            ->whereKey($columnId)
            ->firstOrFail();

        $this->deletingColumnId = (int) $column->getKey();
        $this->columnDeleteConfirmation = '';
        $this->resetValidation();
        $this->dispatch('column-delete-opened', columnName: $column->name, taskCount: (int) $column->tasks_count);
    }

    public function deleteColumn(): void
    {
        abort_if($this->deletingColumnId === null, 404);

        $this->validate(
            ['columnDeleteConfirmation' => ['required', 'string', 'in:confirm']],
            ['columnDeleteConfirmation.in' => 'Type confirm exactly to delete this column.'],
            ['columnDeleteConfirmation' => 'confirmation'],
        );

        $columnId = $this->deletingColumnId;

        DB::transaction(function () use ($columnId): void {
            $column = $this->activeColumns()
                ->whereKey($columnId)
                ->lockForUpdate()
                ->firstOrFail();

            $column->delete();
        }, 3);

        $this->closeDeleteColumnConfirmation();
        $this->dispatch('column-deleted', columnId: $columnId);
    }

    public function closeDeleteColumnConfirmation(): void
    {
        $this->deletingColumnId = null;
        $this->columnDeleteConfirmation = '';
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
        $search = mb_substr(trim($this->search), 0, 100);
        $boardsQuery = fn (HasMany $query) => $query
            ->when($this->boardId !== null, fn (Builder $query) => $query->whereKey($this->boardId))
            ->orderBy('id');

        $project = Project::query()
            ->with([
                'boards' => $boardsQuery,
                'boards.columns' => fn (HasMany $query) => $query->orderBy('position')->orderBy('id'),
                'boards.columns.tasks' => fn (HasMany $query) => $query
                    ->with(['labels', 'checklists'])
                    ->when($search !== '', fn (Builder $query) => $query->where(function (Builder $query) use ($search): void {
                        $query
                            ->where('title', 'like', "%{$search}%")
                            ->orWhereHas('labels', fn (Builder $labelQuery): Builder => $labelQuery->where('name', 'like', "%{$search}%"));
                    }))
                    ->orderBy('position')
                    ->orderBy('id'),
            ])
            ->findOrFail($this->projectId);

        abort_if($this->boardId !== null && $project->boards->isEmpty(), 404);

        $editingTask = $this->editingTaskId === null
            ? null
            : $this->activeTasks()->with('checklists')->whereKey($this->editingTaskId)->firstOrFail();

        $taskDescriptionHtml = RichTextSanitizer::sanitize($this->taskDescription);
        $editTaskDescriptionHtml = RichTextSanitizer::sanitize($this->editTaskDescription);

        return view('livewire.kanban-board', compact('project', 'editingTask', 'taskDescriptionHtml', 'editTaskDescriptionHtml'));
    }

    private function activeColumns(): Builder
    {
        return Column::query()->whereHas('board', fn (Builder $query) => $query
            ->where('project_id', $this->projectId)
            ->when($this->boardId !== null, fn (Builder $query) => $query->whereKey($this->boardId)));
    }

    /**
     * @param  list<int|string>  $labelIds
     * @return list<int>
     */
    private function validatedUserLabelIds(array $labelIds, string $attribute): array
    {
        $labelIds = array_values(array_unique(array_map(
            static fn (int|string $labelId): int => (int) $labelId,
            $labelIds,
        )));

        $ownedLabelsCount = Label::query()
            ->where('user_id', auth()->id())
            ->whereKey($labelIds)
            ->count();

        if ($ownedLabelsCount !== count($labelIds)) {
            throw ValidationException::withMessages([
                $attribute => 'Select only labels that belong to your account.',
            ]);
        }

        return $labelIds;
    }

    private function createOrGetUserLabel(string $name, string $colorCode): Label
    {
        $existingLabel = Label::query()
            ->where('user_id', auth()->id())
            ->where('name', $name)
            ->first();

        if ($existingLabel !== null) {
            return $existingLabel;
        }

        return Label::query()->create([
            'name' => $name,
            'color_code' => $colorCode,
        ]);
    }

    private function activeBoards(): Builder
    {
        return Board::query()
            ->where('project_id', $this->projectId)
            ->when($this->boardId !== null, fn (Builder $query) => $query->whereKey($this->boardId));
    }

    private function activeTasks(): Builder
    {
        return Task::query()->whereHas('column.board', fn (Builder $query) => $query
            ->where('project_id', $this->projectId)
            ->when($this->boardId !== null, fn (Builder $query) => $query->whereKey($this->boardId)));
    }

    private function activeChecklistItems(): Builder
    {
        return ChecklistItem::query()->whereHas('task', function (Builder $query): void {
            $query->whereHas('column.board', fn (Builder $boardQuery) => $boardQuery
                ->where('project_id', $this->projectId)
                ->when($this->boardId !== null, fn (Builder $boardQuery) => $boardQuery->whereKey($this->boardId)));
        });
    }
}
