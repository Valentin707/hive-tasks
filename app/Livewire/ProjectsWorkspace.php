<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Column;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

class ProjectsWorkspace extends Component
{
    #[Locked]
    public ?int $deletingProjectId = null;

    public string $search = '';

    public string $name = '';

    public string $description = '';

    public string $projectDeleteConfirmation = '';

    public function createProject(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:20000'],
        ]);

        abort_unless(auth()->check(), 401);
        $userId = (int) auth()->id();

        DB::transaction(function () use ($validated, $userId): void {
            $project = new Project([
                'name' => $validated['name'],
                'description' => RichTextSanitizer::sanitize($validated['description'] ?? '') ?: null,
            ]);
            $project->user_id = $userId;
            $project->save();

            $board = $project->boards()->create([
                'name' => 'Main Board',
            ]);

            $board->columns()->createMany([
                ['name' => 'Todo', 'position' => 1],
                ['name' => 'In Progress', 'position' => 2],
                ['name' => 'Done', 'position' => 3],
                ['name' => 'Deployed', 'position' => 4],
            ]);
        });

        $this->reset(['name', 'description', 'search']);
        $this->resetValidation();
        $this->dispatch('project-created');
    }

    public function openDeleteProjectConfirmation(int $projectId): void
    {
        abort_unless(auth()->check(), 401);
        abort_if($projectId < 1, 422);

        $project = Project::query()->whereKey($projectId)->firstOrFail();
        $boardCount = $project->boards()->count();
        $columnCount = Column::query()
            ->whereHas('board', fn (Builder $query) => $query->where('project_id', $projectId))
            ->count();
        $taskCount = Task::query()
            ->whereHas('column.board', fn (Builder $query) => $query->where('project_id', $projectId))
            ->count();

        $this->deletingProjectId = $projectId;
        $this->projectDeleteConfirmation = '';
        $this->resetValidation();
        $this->dispatch(
            'project-delete-opened',
            confirmationText: $project->name,
            boardCount: $boardCount,
            columnCount: $columnCount,
            taskCount: $taskCount,
        );
    }

    public function deleteProject(): void
    {
        abort_unless(auth()->check(), 401);
        abort_if($this->deletingProjectId === null, 404);

        $projectId = $this->deletingProjectId;

        DB::transaction(function () use ($projectId): void {
            $project = Project::query()
                ->whereKey($projectId)
                ->lockForUpdate()
                ->firstOrFail();
            $confirmationText = $project->name;

            $this->validate(
                ['projectDeleteConfirmation' => ['required', 'string', Rule::in([$confirmationText])]],
                ['projectDeleteConfirmation.in' => 'Type the project name exactly as shown to confirm deletion.'],
                ['projectDeleteConfirmation' => 'project name confirmation'],
            );

            $project->delete();
        }, 3);

        $this->closeDeleteProjectConfirmation();
        $this->dispatch('project-deleted', projectId: $projectId);
    }

    public function closeDeleteProjectConfirmation(): void
    {
        $this->deletingProjectId = null;
        $this->projectDeleteConfirmation = '';
        $this->resetValidation();
    }

    public function render(): View
    {
        $search = trim($this->search);

        $projects = Project::query()
            ->when($search !== '', fn (Builder $query) => $query->where('name', 'like', "%{$search}%"))
            ->with(['boards' => fn (HasMany $boards) => $boards->orderBy('name')])
            ->withCount([
                'boards as tasks_count' => fn (Builder $boards) => $boards
                    ->join('columns', 'boards.id', '=', 'columns.board_id')
                    ->join('tasks', 'columns.id', '=', 'tasks.column_id'),
            ])
            ->orderBy('name')
            ->get();
        $descriptionHtml = RichTextSanitizer::sanitize($this->description);

        return view('livewire.projects-workspace', compact('projects', 'descriptionHtml'));
    }
}
