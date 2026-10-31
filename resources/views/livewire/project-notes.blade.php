<section
    aria-labelledby="project-notes-title"
    x-data="{ createNoteOpen: false }"
    x-on:note-created.window="createNoteOpen = false"
    x-on:keydown.escape.window="if (createNoteOpen) { createNoteOpen = false; $wire.closeCreateNoteModal() }"
>
    <div class="mb-6 flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
        <div>
            <p class="mb-2 text-sm font-medium text-indigo-600 dark:text-indigo-400">Project notes</p>
            <h2 id="project-notes-title" class="text-xl font-semibold tracking-tight text-gray-900 sm:text-2xl">Notes for {{ $project->name }}</h2>
            <p class="mt-1 text-sm text-gray-500">Select a note to review its full content.</p>
        </div>

        <div class="flex w-full flex-col gap-3 sm:flex-row xl:w-auto">
            <label class="relative min-w-0 flex-1 sm:w-72" for="project-notes-search">
                <span class="sr-only">Search project notes</span>
                <svg class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <circle cx="10.8" cy="10.8" r="6.3" />
                    <path stroke-linecap="round" d="m15.5 15.5 4.25 4.25" />
                </svg>
                <input id="project-notes-search" type="search" wire:model.live.debounce.300ms="search" placeholder="Search project notes..." class="form-input pl-9">
            </label>
            <button type="button" @click="createNoteOpen = true; $nextTick(() => $refs.newNoteTitle.focus())" class="button-primary w-full shrink-0 gap-2 whitespace-nowrap sm:w-auto">
                <span aria-hidden="true" class="text-base leading-none">+</span>
                New project note
            </button>
        </div>
    </div>

    <div class="grid min-h-[34rem] gap-5 lg:grid-cols-[minmax(17rem,0.85fr)_minmax(0,1.75fr)]">
        <aside aria-label="Project notes list" class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-200 dark:bg-gray-100 dark:shadow-black/20">
            <div class="border-b border-gray-200 p-4 dark:border-gray-200">
                <h3 class="text-sm font-semibold text-gray-900">Your project notes</h3>
                <p class="mt-0.5 text-xs text-gray-500">{{ $notes->count() }} {{ \Illuminate\Support\Str::plural('note', $notes->count()) }}</p>
            </div>

            <div class="max-h-[38rem] space-y-1 overflow-y-auto p-2">
                @forelse ($notes as $note)
                    <button
                        type="button"
                        wire:key="project-note-list-{{ $note->getKey() }}"
                        wire:click="selectNote({{ $note->getKey() }})"
                        @if ($selectedNoteId === (int) $note->getKey()) aria-current="page" @endif
                        class="block w-full rounded-xl px-3 py-2.5 text-left transition focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-indigo-500 {{ $selectedNoteId === (int) $note->getKey() ? 'bg-indigo-50 ring-1 ring-inset ring-indigo-200 dark:bg-indigo-950/40 dark:ring-indigo-800/70' : 'hover:bg-gray-50 dark:hover:bg-gray-50' }}"
                    >
                        <span class="block truncate text-sm font-medium text-gray-800">{{ $note->title !== '' ? $note->title : 'Untitled Note' }}</span>
                        <span class="mt-1 block truncate text-xs text-gray-500">{{ $note->content ? \Illuminate\Support\Str::limit(html_entity_decode(strip_tags($note->content), ENT_QUOTES | ENT_HTML5, 'UTF-8'), 90) : 'No content yet' }}</span>
                        <span class="mt-2 block text-[11px] text-gray-400">Updated {{ $note->updated_at->diffForHumans() }}</span>
                    </button>
                @empty
                    <div class="px-4 py-10 text-center">
                        <p class="text-sm font-medium text-gray-700">{{ trim($search) === '' ? 'No notes linked to this project' : 'No matching notes' }}</p>
                        <p class="mt-1 text-xs text-gray-500">{{ trim($search) === '' ? 'Create a project note to get started.' : 'Try another search.' }}</p>
                    </div>
                @endforelse
            </div>
        </aside>

        <section aria-label="Project note preview" class="flex min-h-[34rem] flex-col rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-200 dark:bg-gray-100 dark:shadow-black/20">
            @if ($activeNote)
                @if ($isEditingNote)
                    <form wire:submit="saveNoteEdits" class="flex flex-1 flex-col">
                        <div class="flex flex-col gap-3 border-b border-gray-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-7 dark:border-gray-200">
                            <div class="min-w-0 flex-1">
                                <label for="project-note-title-{{ $activeNote->getKey() }}" class="sr-only">Note title</label>
                                <input id="project-note-title-{{ $activeNote->getKey() }}" type="text" maxlength="255" wire:model="noteTitle" placeholder="Untitled Note" class="w-full border-0 bg-transparent p-0 text-lg font-semibold text-gray-900 outline-none placeholder:text-gray-400 focus:ring-0 sm:text-xl">
                                @error('noteTitle')
                                    <p class="form-error">{{ $message }}</p>
                                @enderror
                                <p class="mt-1 text-xs text-gray-400">Updated {{ $activeNote->updated_at->diffForHumans() }}</p>
                            </div>
                            <div class="flex shrink-0 items-center justify-between gap-3 sm:justify-end">
                                <span class="text-xs text-gray-400">Save your changes when you are done</span>
                                <button type="submit" wire:loading.attr="disabled" wire:target="saveNoteEdits" class="button-secondary px-3 py-1.5 text-xs">
                                    <span wire:loading.remove wire:target="saveNoteEdits">Save changes</span>
                                    <span wire:loading wire:target="saveNoteEdits">Saving...</span>
                                </button>
                            </div>
                        </div>

                        <div
                            wire:key="quill-project-note-editor-{{ $selectedNoteId }}"
                            x-data="quillRichTextEditor({ placeholder: 'Start writing...', ariaLabel: 'Project note content', minHeight: '28rem' })"
                            x-init="$nextTick(() => initialize())"
                            class="flex-1 p-5 sm:px-7"
                        >
                            <div class="quill-rich-text-editor" wire:ignore>
                                <div x-ref="editor"></div>
                            </div>
                            <textarea x-ref="contentInput" wire:model="noteContent" aria-hidden="true" tabindex="-1" class="hidden">{{ $noteContent }}</textarea>
                            <textarea x-ref="initialHtml" aria-hidden="true" tabindex="-1" class="hidden">{{ $activeNoteHtml }}</textarea>
                            @error('noteContent')
                                <p class="form-error">{{ $message }}</p>
                            @enderror
                        </div>
                    </form>
                @else
                    <div class="flex flex-col gap-3 border-b border-gray-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-7 dark:border-gray-200">
                        <div class="min-w-0">
                            <h3 class="break-words text-lg font-semibold text-gray-900 sm:text-xl">{{ $activeNote->title !== '' ? $activeNote->title : 'Untitled Note' }}</h3>
                            <p class="mt-1 text-xs text-gray-400">Updated {{ $activeNote->updated_at->diffForHumans() }}</p>
                        </div>
                        <div class="flex shrink-0 items-center gap-2">
                            <button type="button" wire:click="editNote({{ $activeNote->getKey() }})" wire:loading.attr="disabled" wire:target="editNote" class="button-secondary px-3 py-1.5 text-xs">Edit note</button>
                            <button type="button" wire:click="deleteNote({{ $activeNote->getKey() }})" wire:confirm="Delete this project note? This action cannot be undone." wire:loading.attr="disabled" wire:target="deleteNote" class="inline-flex items-center gap-1.5 rounded-lg bg-red-50 px-3 py-1.5 text-xs font-semibold text-red-700 transition hover:bg-red-100 disabled:cursor-not-allowed disabled:opacity-50 dark:bg-red-950/40 dark:text-red-300 dark:hover:bg-red-950/70">
                                <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M10 11v6m4-6v6M6 7l1 13h10l1-13M9 7V4h6v3" />
                                </svg>
                                Delete note
                            </button>
                        </div>
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
                    <div class="flex size-12 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 dark:bg-indigo-950/60 dark:text-indigo-300">
                        <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M7 3.75h7l5 5v11.5H7a2 2 0 0 1-2-2v-12.5a2 2 0 0 1 2-2Z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14 3.75v5h5M8.5 13h7M8.5 16.5h7" />
                        </svg>
                    </div>
                    <h3 class="mt-4 text-sm font-semibold text-gray-900">{{ trim($search) === '' ? 'Choose a project note to review' : 'No matching notes' }}</h3>
                    <p class="mt-1 max-w-xs text-sm text-gray-500">{{ trim($search) === '' ? 'Select a note from the sidebar or create a new one.' : 'Try another search.' }}</p>
                </div>
            @endif
        </section>
    </div>

    <div
        x-cloak
        x-show="createNoteOpen"
        x-transition.opacity
        class="fixed inset-0 z-[70] flex items-start justify-center overflow-y-auto bg-gray-950/50 p-4 sm:items-center"
        @click.self="createNoteOpen = false; $wire.closeCreateNoteModal()"
    >
        <section role="dialog" aria-modal="true" aria-labelledby="create-project-note-title" class="max-h-[calc(100dvh-2rem)] w-full max-w-3xl overflow-y-auto rounded-2xl border border-gray-200 bg-white p-6 shadow-xl sm:p-7">
            <div class="mb-6 flex items-start justify-between gap-4">
                <div>
                    <p class="text-sm font-medium text-indigo-600 dark:text-indigo-400">{{ $project->name }}</p>
                    <h2 id="create-project-note-title" class="mt-1 text-lg font-semibold text-gray-900">Create a project note</h2>
                    <p class="mt-1 text-sm text-gray-500">Write and save a note directly to this project.</p>
                    <p class="mt-3 inline-flex items-center gap-1.5 rounded-full bg-indigo-50 px-2.5 py-1 text-xs font-medium text-indigo-700 dark:bg-indigo-950/50 dark:text-indigo-300">
                        This note will be linked to {{ $project->name }}
                    </p>
                </div>
                <button type="button" @click="createNoteOpen = false; $wire.closeCreateNoteModal()" class="rounded-lg p-1 text-gray-400 transition hover:bg-gray-100 hover:text-gray-700" aria-label="Close dialog">×</button>
            </div>

            <form wire:submit="createNote">
                <div class="mb-5">
                    <label for="new-project-note-title" class="form-label">Title</label>
                    <input x-ref="newNoteTitle" id="new-project-note-title" type="text" wire:model="newNoteTitle" maxlength="255" required class="form-input" @error('newNoteTitle') aria-invalid="true" aria-describedby="new-project-note-title-error" @enderror>
                    @error('newNoteTitle')
                        <p id="new-project-note-title-error" class="form-error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-6">
                    <p class="form-label">Content <span class="font-normal text-gray-400">(optional)</span></p>
                    <div
                        x-data="quillRichTextEditor({ placeholder: 'Start writing...', ariaLabel: 'New project note content', minHeight: '14rem' })"
                        x-init="$nextTick(() => initialize())"
                        x-on:note-created.window="clear()"
                        x-on:note-create-cancelled.window="clear()"
                    >
                        <div class="quill-rich-text-editor" wire:ignore>
                            <div x-ref="editor"></div>
                        </div>
                        <textarea x-ref="contentInput" wire:model="newNoteContent" aria-hidden="true" tabindex="-1" class="hidden">{{ $newNoteContent }}</textarea>
                        <textarea x-ref="initialHtml" aria-hidden="true" tabindex="-1" class="hidden">{{ $newNoteContentHtml }}</textarea>
                    </div>
                    @error('newNoteContent')
                        <p id="new-project-note-content-error" class="form-error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                    <button type="button" @click="createNoteOpen = false; $wire.closeCreateNoteModal()" class="button-secondary">Cancel</button>
                    <button type="submit" class="button-primary" wire:loading.attr="disabled" wire:target="createNote">
                        <span wire:loading.remove wire:target="createNote">Create note</span>
                        <span wire:loading wire:target="createNote">Creating...</span>
                    </button>
                </div>
            </form>
        </section>
    </div>
</section>
