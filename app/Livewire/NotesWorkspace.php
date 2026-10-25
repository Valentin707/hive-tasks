<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Note;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;
use Livewire\Component;

class NotesWorkspace extends Component
{
    public string $search = '';

    public function render(): View
    {
        $search = trim($this->search);

        $notes = Note::query()
            ->when($search !== '', fn (Builder $query) => $query->where('title', 'like', "%{$search}%"))
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->get();

        return view('livewire.notes-workspace', compact('notes'));
    }
}
