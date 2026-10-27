<section aria-labelledby="notes-title" x-data="{ createNoteOpen: false }" x-on:note-created.window="createNoteOpen = false" x-on:keydown.escape.window="createNoteOpen = false">
    <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="mb-2 text-sm font-medium text-indigo-600">Workspace</p>
            <h1 id="notes-title" class="text-2xl font-semibold tracking-tight text-gray-900 sm:text-3xl">Notes</h1>
            <p class="mt-2 text-sm text-gray-500">Keep project thoughts and references together.</p>
        </div>

        <label class="relative w-full sm:w-72" for="notes-search">
            <span class="sr-only">Search notes</span>
            <svg class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <circle cx="10.8" cy="10.8" r="6.3" />
                <path stroke-linecap="round" d="m15.5 15.5 4.25 4.25" />
            </svg>
            <input id="notes-search" type="search" wire:model.live.debounce.300ms="search" placeholder="Search notes..." class="form-input pl-9">
        </label>
    </div>

    <div class="grid min-h-[34rem] gap-5 lg:grid-cols-[minmax(17rem,0.85fr)_minmax(0,1.75fr)]">
        <aside aria-label="Notes list" class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
            <div class="flex items-center justify-between gap-3 border-b border-gray-200 p-4">
                <div>
                    <h2 class="text-sm font-semibold text-gray-900">Your notes</h2>
                    <p class="mt-0.5 text-xs text-gray-500">{{ $notes->count() }} {{ \Illuminate\Support\Str::plural('note', $notes->count()) }}</p>
                </div>
                <button type="button" @click="createNoteOpen = true" class="button-primary shrink-0 gap-1.5 px-3 py-2 text-xs">
                    <span aria-hidden="true" class="text-base leading-none">+</span>
                    <span>Add New</span>
                </button>
            </div>

            <div class="max-h-[38rem] space-y-1 overflow-y-auto p-2">
                @forelse ($notes as $note)
                    <div wire:key="note-{{ $note->id }}" class="flex items-start gap-1 rounded-xl p-1 transition {{ $selectedNoteId === (int) $note->getKey() ? 'bg-indigo-50 ring-1 ring-inset ring-indigo-200 dark:bg-indigo-950/40 dark:ring-indigo-800/70' : 'hover:bg-gray-50' }}">
                        <button
                            type="button"
                            wire:click="selectNote({{ $note->id }})"
                            @if ($selectedNoteId === (int) $note->getKey()) aria-current="page" @endif
                            class="min-w-0 flex-1 rounded-lg px-3 py-2.5 text-left focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-indigo-500"
                        >
                            <span class="block truncate text-sm font-medium text-gray-800">{{ $note->title !== '' ? $note->title : 'Untitled Note' }}</span>
                            <span class="mt-1 block truncate text-xs text-gray-500">{{ $note->content ? \Illuminate\Support\Str::limit(html_entity_decode(strip_tags($note->content), ENT_QUOTES | ENT_HTML5, 'UTF-8'), 90) : 'No content yet' }}</span>
                            <span class="mt-2 block text-[11px] text-gray-400">Updated {{ $note->updated_at->diffForHumans() }}</span>
                        </button>

                        <button
                            type="button"
                            wire:click="editNote({{ $note->id }})"
                            aria-label="Edit {{ $note->title !== '' ? $note->title : 'Untitled Note' }}"
                            title="Edit note"
                            class="mt-1 inline-flex size-9 shrink-0 items-center justify-center rounded-lg text-gray-400 transition hover:bg-indigo-50 hover:text-indigo-600 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                        >
                            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m14 5 5 5M4 20l4.25-.85L19 8.4a2.12 2.12 0 0 0-3-3L5.25 16.15 4 20Z" />
                            </svg>
                        </button>

                        <button
                            type="button"
                            wire:click="deleteNote({{ $note->id }})"
                            wire:confirm="Delete this note? This action cannot be undone."
                            wire:loading.attr="disabled"
                            wire:target="deleteNote({{ $note->id }})"
                            aria-label="Delete {{ $note->title !== '' ? $note->title : 'Untitled Note' }}"
                            title="Delete note"
                            class="mt-1 inline-flex size-9 shrink-0 items-center justify-center rounded-lg text-gray-400 transition hover:bg-red-50 hover:text-red-600 focus:outline-none focus:ring-2 focus:ring-red-500 disabled:opacity-50"
                        >
                            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M10 11v6m4-6v6M6 7l1 13h10l1-13M9 7V4h6v3" />
                            </svg>
                        </button>
                    </div>
                @empty
                    <div class="px-4 py-10 text-center">
                        <p class="text-sm font-medium text-gray-700">{{ trim($search) === '' ? 'No notes yet' : 'No matching notes' }}</p>
                        <p class="mt-1 text-xs text-gray-500">{{ trim($search) === '' ? 'Create a note to get started.' : 'Try another search.' }}</p>
                    </div>
                @endforelse
            </div>
        </aside>

        <section aria-label="Note editor" class="flex min-h-[34rem] flex-col rounded-2xl border border-gray-200 bg-white shadow-sm">
            @if ($activeNote)
                @if ($isEditingNote)
                    <div class="flex items-center justify-between gap-4 border-b border-gray-200 px-5 py-4 sm:px-7">
                        <div class="min-w-0 flex-1">
                            <label for="note-title" class="sr-only">Note title</label>
                            <input id="note-title" type="text" maxlength="255" wire:model.live.debounce.1000ms="noteTitle" placeholder="Untitled Note" class="w-full border-0 bg-transparent p-0 text-lg font-semibold text-gray-900 outline-none placeholder:text-gray-400 focus:ring-0 sm:text-xl">
                        </div>
                        <span wire:loading wire:target="noteTitle,noteContent" class="shrink-0 text-xs text-indigo-600">Saving...</span>
                        <span wire:loading.remove wire:target="noteTitle,noteContent" class="shrink-0 text-xs text-gray-400">Saved automatically</span>
                        <button type="button" wire:click="finishNoteEditing" class="button-secondary shrink-0 px-3 py-1.5 text-xs">Done</button>
                    </div>

                    <div
                        wire:key="quill-note-editor-{{ $selectedNoteId }}"
                        x-data="quillRichTextEditor({ placeholder: 'Start writing...', ariaLabel: 'Note content', minHeight: '28rem' })"
                        x-init="$nextTick(() => initialize())"
                        class="flex-1 p-5 sm:px-7"
                    >
                        <div class="quill-rich-text-editor" wire:ignore>
                            <div x-ref="editor"></div>
                        </div>
                        <textarea x-ref="contentInput" wire:model.live.debounce.1000ms="noteContent" aria-hidden="true" tabindex="-1" class="hidden">{{ $noteContent }}</textarea>
                        <textarea x-ref="initialHtml" aria-hidden="true" tabindex="-1" class="hidden">{{ $activeNoteHtml }}</textarea>
                    </div>
                @else
                    <div class="border-b border-gray-200 px-5 py-4 sm:px-7">
                        <h2 class="break-words text-lg font-semibold text-gray-900 sm:text-xl">{{ $activeNote->title !== '' ? $activeNote->title : 'Untitled Note' }}</h2>
                        <p class="mt-1 text-xs text-gray-400">Updated {{ $activeNote->updated_at->diffForHumans() }}</p>
                    </div>
                    <div class="min-h-[28rem] flex-1 break-words px-5 py-5 text-sm leading-7 text-gray-700 sm:px-7">
                        @if ($activeNoteHtml !== '')
                            <div class="ql-editor quill-content-display">{!! $activeNoteHtml !!}</div>
                        @else
                            <span class="italic text-gray-400">No note content yet.</span>
                        @endif
                    </div>
                @endif
            @else
                <div class="flex flex-1 flex-col items-center justify-center px-6 py-14 text-center">
                    <div class="flex size-12 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600">
                        <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M7 3.75h7l5 5v11.5H7a2 2 0 0 1-2-2v-12.5a2 2 0 0 1 2-2Z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14 3.75v5h5M8.5 13h7M8.5 16.5h7" />
                        </svg>
                    </div>
                    <h2 class="mt-4 text-sm font-semibold text-gray-900">Choose a note to edit</h2>
                    <p class="mt-1 max-w-xs text-sm text-gray-500">Select a note from the sidebar or create a new one.</p>
                </div>
            @endif
        </section>
    </div>

    <div x-cloak x-show="createNoteOpen" x-transition.opacity class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-gray-950/40 p-4 sm:items-center" @click.self="createNoteOpen = false">
        <section role="dialog" aria-modal="true" aria-labelledby="create-note-title" class="max-h-[calc(100dvh-2rem)] w-full max-w-2xl overflow-y-auto rounded-2xl bg-white p-6 shadow-xl sm:p-7">
            <div class="mb-6 flex items-start justify-between gap-4">
                <div>
                    <h2 id="create-note-title" class="text-lg font-semibold text-gray-900">Create a note</h2>
                    <p class="mt-1 text-sm text-gray-500">Add a title and start with an optional note.</p>
                </div>
                <button type="button" @click="createNoteOpen = false" class="rounded-lg p-1 text-gray-400 transition hover:bg-gray-100 hover:text-gray-700" aria-label="Close dialog">×</button>
            </div>

            <form wire:submit="createNote">
                <div class="mb-5">
                    <label for="new-note-title" class="form-label">Title</label>
                    <input id="new-note-title" type="text" wire:model="newNoteTitle" maxlength="255" required class="form-input" @error('newNoteTitle') aria-invalid="true" aria-describedby="new-note-title-error" @enderror>
                    @error('newNoteTitle')
                        <p id="new-note-title-error" class="form-error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-6">
                    <p class="form-label">Content <span class="font-normal text-gray-400">(optional)</span></p>
                    <div
                        x-data="quillRichTextEditor({ placeholder: 'Start writing...', ariaLabel: 'New note content', minHeight: '12rem' })"
                        x-init="$nextTick(() => initialize())"
                        x-on:note-created.window="clear()"
                    >
                        <div class="quill-rich-text-editor" wire:ignore>
                            <div x-ref="editor"></div>
                        </div>
                        <textarea x-ref="contentInput" wire:model="newNoteContent" aria-hidden="true" tabindex="-1" class="hidden">{{ $newNoteContent }}</textarea>
                        <textarea x-ref="initialHtml" aria-hidden="true" tabindex="-1" class="hidden">{{ $newNoteContentHtml }}</textarea>
                    </div>
                    @error('newNoteContent')
                        <p id="new-note-content-error" class="form-error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                    <button type="button" @click="createNoteOpen = false" class="button-secondary">Cancel</button>
                    <button type="submit" class="button-primary" wire:loading.attr="disabled" wire:target="createNote">
                        <span wire:loading.remove wire:target="createNote">Create note</span>
                        <span wire:loading wire:target="createNote">Creating...</span>
                    </button>
                </div>
            </form>
        </section>
    </div>
</section>
