<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Note;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

class ProjectNotes extends Component
{
    #[Locked]
    public int $projectId;

    #[Locked]
    public ?int $selectedNoteId = null;

    public string $search = '';

    public string $newNoteTitle = 'Untitled Note';

    public string $newNoteContent = '';

    public function mount(int $projectId): void
    {
        $this->projectId = $projectId;
        $latestNoteId = $this->ownedProject()->notes()
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->value('id');
        $this->selectedNoteId = $latestNoteId === null ? null : (int) $latestNoteId;
    }

    public function selectNote(int $noteId): void
    {
        abort_if($noteId < 1, 422);

        $this->ownedProject()->notes()->whereKey($noteId)->firstOrFail();
        $this->selectedNoteId = $noteId;
    }

    public function closeCreateNoteModal(): void
    {
        $this->ownedProject();
        $this->reset(['newNoteTitle', 'newNoteContent']);
        $this->newNoteTitle = 'Untitled Note';
        $this->resetValidation();
        $this->dispatch('note-create-cancelled');
    }

    public function createNote(): void
    {
        $project = $this->ownedProject();
        $validated = $this->validate([
            'newNoteTitle' => ['required', 'string', 'max:255'],
            'newNoteContent' => ['nullable', 'string', 'max:100000'],
        ]);

        $note = new Note([
            'title' => $validated['newNoteTitle'],
            'content' => RichTextSanitizer::sanitize($validated['newNoteContent'] ?? ''),
        ]);
        $project->notes()->save($note);

        $this->search = '';
        $this->selectedNoteId = (int) $note->getKey();
        $this->reset(['newNoteTitle', 'newNoteContent']);
        $this->newNoteTitle = 'Untitled Note';
        $this->resetValidation();
        $this->dispatch('note-created');
    }

    public function render(): View
    {
        $project = $this->ownedProject();
        $search = trim($this->search);
        $notes = $project->notes()
            ->when($search !== '', static function (Builder $query) use ($search): void {
                $query->where('title', 'like', '%'.$search.'%');
            })
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->get();
        $activeNote = $this->selectedNoteId === null
            ? null
            : $notes->firstWhere('id', $this->selectedNoteId);

        if ($activeNote === null) {
            $activeNote = $notes->first();
            $this->selectedNoteId = $activeNote === null ? null : (int) $activeNote->getKey();
        }

        $activeNoteHtml = $activeNote === null
            ? ''
            : RichTextSanitizer::sanitize($activeNote->content ?? '');
        $newNoteContentHtml = RichTextSanitizer::sanitize($this->newNoteContent);

        return view('livewire.project-notes', compact('project', 'notes', 'activeNote', 'activeNoteHtml', 'newNoteContentHtml', 'search'));
    }

    private function ownedProject(): Project
    {
        $user = Auth::user();

        abort_unless($user instanceof User, 401);

        return $user->projects()->findOrFail($this->projectId);
    }
}
