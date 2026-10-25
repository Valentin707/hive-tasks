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

    public function mount(int $projectId, ?int $boardId = null): void
    {
        $this->projectId = $projectId;
        $this->boardId = $boardId;
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

        return view('livewire.kanban-board', compact('project'));
    }
}
