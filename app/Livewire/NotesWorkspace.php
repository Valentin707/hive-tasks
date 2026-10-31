<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Note;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;

class NotesWorkspace extends Component
{
    public string $search = '';

    #[Locked]
    public ?int $selectedNoteId = null;

    #[Url(as: 'current_project_id')]
    public int|string|null $currentProjectId = null;

    public string $noteTitle = '';

    public string $noteContent = '';

    #[Locked]
    public bool $isEditingNote = false;

    public string $newNoteTitle = 'Untitled Note';

    public string $newNoteContent = '';

    public function mount(): void
    {
        $project = $this->contextProject();
        $this->currentProjectId = $project === null ? null : (int) $project->getKey();
    }

    public function createNote(): void
    {
        $validated = $this->validate([
            'newNoteTitle' => ['required', 'string', 'max:255'],
            'newNoteContent' => ['nullable', 'string', 'max:100000'],
        ]);

        $user = $this->authenticatedUser();
        $project = $this->contextProject();

        $note = new Note;
        $note->user_id = (int) $user->getKey();
        $note->project_id = $project?->getKey();
        $note->title = $validated['newNoteTitle'];
        $note->content = RichTextSanitizer::sanitize($validated['newNoteContent'] ?? '');
        $note->save();

        $this->search = '';
        $this->selectNoteState($note);
        $this->isEditingNote = false;
        $this->reset(['newNoteTitle', 'newNoteContent']);
        $this->newNoteTitle = 'Untitled Note';
        $this->resetValidation();
        $this->dispatch('note-created');
    }

    public function updateNoteProject(int|string|null $projectId): void
    {
        $user = $this->authenticatedUser();
        abort_if($this->selectedNoteId === null, 404);

        $projectId = $projectId === '' ? null : $projectId;
        $validated = validator(['projectId' => $projectId], [
            'projectId' => [
                'nullable',
                'integer',
                'min:1',
                Rule::exists('projects', 'id')->where('user_id', $user->getKey()),
            ],
        ])->validate();

        $note = Note::query()->findOrFail($this->selectedNoteId);
        $note->project_id = $validated['projectId'] === null ? null : (int) $validated['projectId'];
        $note->save();
    }

    public function selectNote(int $noteId): void
    {
        abort_if($noteId < 1, 422);

        $note = Note::query()->findOrFail($noteId);

        $this->selectNoteState($note);
        $this->isEditingNote = false;
        $this->resetValidation();
    }

    public function editNote(int $noteId): void
    {
        abort_if($noteId < 1, 422);

        $note = Note::query()->findOrFail($noteId);

        $this->selectNoteState($note);
        $this->isEditingNote = true;
        $this->resetValidation();
    }

    public function saveNoteEdits(): void
    {
        $this->saveSelectedNote();
        $this->isEditingNote = false;
        $this->resetValidation();
    }

    public function deleteNote(int $noteId): void
    {
        abort_if($noteId < 1, 422);

        $note = Note::query()->findOrFail($noteId);
        $note->delete();

        if ($this->selectedNoteId === $noteId) {
            $this->clearSelectedNote();
        }

        $this->resetValidation();
    }

    public function render(): View
    {
        $user = $this->authenticatedUser();
        $search = trim($this->search);

        $notes = Note::query()
            ->with('project:id,name')
            ->when($search !== '', fn (Builder $query) => $query->where('title', 'like', "%{$search}%"))
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->get();

        $activeNote = $this->selectedNoteId === null
            ? null
            : Note::query()->with('project')->find($this->selectedNoteId);
        $activeNoteHtml = $activeNote === null
            ? ''
            : RichTextSanitizer::sanitize($activeNote->content ?? '');
        $newNoteContentHtml = RichTextSanitizer::sanitize($this->newNoteContent);
        $projects = $user->projects()->orderBy('name')->get();
        $contextProject = $this->currentProjectId === null || $this->currentProjectId === ''
            ? null
            : $projects->firstWhere('id', (int) $this->currentProjectId);

        return view('livewire.notes-workspace', compact('notes', 'activeNote', 'activeNoteHtml', 'newNoteContentHtml', 'projects', 'contextProject'));
    }

    private function selectNoteState(Note $note): void
    {
        $this->selectedNoteId = (int) $note->getKey();
        $this->noteTitle = $note->title;
        $this->noteContent = $note->content ?? '';
    }

    private function clearSelectedNote(): void
    {
        $this->selectedNoteId = null;
        $this->noteTitle = '';
        $this->noteContent = '';
        $this->isEditingNote = false;
    }

    private function saveSelectedNote(): void
    {
        abort_if($this->selectedNoteId === null, 404);

        $validated = $this->validate([
            'noteTitle' => ['string', 'max:255'],
            'noteContent' => ['string', 'max:100000'],
        ]);

        $note = Note::query()->findOrFail($this->selectedNoteId);
        $note->title = $validated['noteTitle'];
        $note->content = RichTextSanitizer::sanitize($validated['noteContent']);
        $note->save();
    }

    private function authenticatedUser(): User
    {
        $user = Auth::user();

        abort_unless($user instanceof User, 401);

        return $user;
    }

    private function contextProject(): ?Project
    {
        if ($this->currentProjectId === null || $this->currentProjectId === '') {
            return null;
        }

        $projectId = filter_var($this->currentProjectId, FILTER_VALIDATE_INT);
        abort_unless(is_int($projectId) && $projectId > 0, 404);

        return $this->authenticatedUser()->projects()->whereKey($projectId)->firstOrFail();
    }
}
