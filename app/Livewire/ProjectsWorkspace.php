<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Project;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Component;

class ProjectsWorkspace extends Component
{
    public string $search = '';

    public string $name = '';

    public string $description = '';

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
                'description' => $validated['description'] ?: null,
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

        return view('livewire.projects-workspace', compact('projects'));
    }
}
