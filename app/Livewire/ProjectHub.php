<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Component;

class ProjectHub extends Component
{
    public function render(): View
    {
        $user = Auth::user();
        abort_unless($user instanceof User, 403);

        $projects = $user->projects()
            ->with([
                'boards' => static function (HasMany $boards): void {
                    $boards->select(['id', 'project_id', 'name'])->orderBy('id');
                },
            ])
            ->withCount([
                'boards as task_count' => static function (Builder $boards): void {
                    $boards->join('columns', 'boards.id', '=', 'columns.board_id')
                        ->join('tasks', 'columns.id', '=', 'tasks.column_id');
                },
                'boards as completed_task_count' => static function (Builder $boards): void {
                    $boards->join('columns', 'boards.id', '=', 'columns.board_id')
                        ->join('tasks', 'columns.id', '=', 'tasks.column_id')
                        ->whereIn('columns.name', ['Done', 'Deployed']);
                },
            ])
            ->orderBy('name')
            ->get();

        $totalTaskCount = (int) $projects->sum('task_count');
        $completedTaskCount = (int) $projects->sum('completed_task_count');
        $totalNoteCount = $user->notes()->count();
        $workspaceCompletionPercentage = $totalTaskCount === 0
            ? 0
            : (int) round(($completedTaskCount / $totalTaskCount) * 100);

        /**
         * @var array<int, array{
         *     id: int,
         *     name: string,
         *     task_count: int,
         *     completed_task_count: int,
         *     completion_percentage: int,
         *     board_url: string|null
         * }> $activeProjects
         */
        $activeProjects = $projects
            ->map(static function (Project $project): array {
                $taskCount = (int) $project->task_count;
                $completedTaskCount = (int) $project->completed_task_count;
                $board = $project->boards->first();

                return [
                    'id' => (int) $project->getKey(),
                    'name' => $project->name,
                    'task_count' => $taskCount,
                    'completed_task_count' => $completedTaskCount,
                    'completion_percentage' => $taskCount === 0
                        ? 0
                        : (int) round(($completedTaskCount / $taskCount) * 100),
                    'board_url' => $board === null
                        ? null
                        : route('projects.board', ['project' => $project, 'board' => $board]),
                ];
            })
            ->all();

        $recentTasks = Task::query()
            ->with([
                'column:id,board_id,name',
                'column.board:id,project_id,name',
                'column.board.project:id,name',
            ])
            ->latest('updated_at')
            ->latest('id')
            ->limit(5)
            ->get();

        return view('livewire.project-hub', compact(
            'totalTaskCount',
            'totalNoteCount',
            'workspaceCompletionPercentage',
            'activeProjects',
            'recentTasks',
        ));
    }
}
