<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Project;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

class ProjectDescription extends Component
{
    #[Locked]
    public int $projectId;

    public string $description = '';

    public bool $isEditing = false;

    public function mount(int $projectId): void
    {
        $this->projectId = $projectId;
        $this->description = $this->ownedProject()->description ?? '';
    }

    public function startEditing(): void
    {
        $this->description = $this->ownedProject()->description ?? '';
        $this->isEditing = true;
        $this->resetValidation();
    }

    public function cancelEditing(): void
    {
        $this->description = $this->ownedProject()->description ?? '';
        $this->isEditing = false;
        $this->resetValidation();
    }

    public function saveDescription(): void
    {
        $validated = $this->validate([
            'description' => ['nullable', 'string', 'max:20000'],
        ]);

        $project = $this->ownedProject();
        $project->description = $validated['description'] ?: null;
        $project->save();

        $this->description = $project->description ?? '';
        $this->isEditing = false;
        $this->resetValidation();
    }

    public function render(): View
    {
        return view('livewire.project-description', [
            'project' => $this->ownedProject(),
        ]);
    }

    private function ownedProject(): Project
    {
        $user = auth()->user();

        abort_unless($user !== null, 401);

        return $user->projects()->findOrFail($this->projectId);
    }
}
