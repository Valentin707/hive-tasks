<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Note;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

class NotesWorkspace extends Component
{
    public string $search = '';

    #[Locked]
    public ?int $selectedNoteId = null;

    public string $noteTitle = '';

    public string $noteContent = '';

    #[Locked]
    public bool $isEditingNote = false;

    public string $newNoteTitle = 'Untitled Note';

    public string $newNoteContent = '';

    public function createNote(): void
    {
        $validated = $this->validate([
            'newNoteTitle' => ['required', 'string', 'max:255'],
            'newNoteContent' => ['nullable', 'string', 'max:100000'],
        ]);

        $userId = Auth::id();
        abort_if($userId === null, 403);

        $note = new Note;
        $note->user_id = (int) $userId;
        $note->title = $validated['newNoteTitle'];
        $note->content = $validated['newNoteContent'] ?? '';
        $note->save();

        $this->search = '';
        $this->selectNoteState($note);
        $this->isEditingNote = false;
        $this->reset(['newNoteTitle', 'newNoteContent']);
        $this->newNoteTitle = 'Untitled Note';
        $this->resetValidation();
        $this->dispatch('note-created');
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

    public function finishNoteEditing(): void
    {
        $this->saveSelectedNote();
        $this->isEditingNote = false;
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

    public function updatedNoteTitle(): void
    {
        $this->saveSelectedNote();
    }

    public function updatedNoteContent(): void
    {
        $this->saveSelectedNote();
    }

    public function render(): View
    {
        $search = trim($this->search);

        $notes = Note::query()
            ->when($search !== '', fn (Builder $query) => $query->where('title', 'like', "%{$search}%"))
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->get();

        $activeNote = $this->selectedNoteId === null
            ? null
            : Note::query()->find($this->selectedNoteId);

        return view('livewire.notes-workspace', compact('notes', 'activeNote'));
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
        if ($this->selectedNoteId === null || ! $this->isEditingNote) {
            return;
        }

        $validated = $this->validate([
            'noteTitle' => ['string', 'max:255'],
            'noteContent' => ['string', 'max:100000'],
        ]);

        $note = Note::query()->findOrFail($this->selectedNoteId);
        $note->title = $validated['noteTitle'];
        $note->content = $validated['noteContent'];
        $note->save();
    }
}
