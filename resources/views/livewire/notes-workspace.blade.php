<section aria-labelledby="notes-title">
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

    @if ($notes->isNotEmpty())
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @foreach ($notes as $note)
                <article wire:key="note-{{ $note->id }}" class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                    <h2 class="text-base font-semibold text-gray-900">{{ $note->title }}</h2>
                    @if ($note->content)
                        <p class="mt-3 whitespace-pre-line text-sm leading-6 text-gray-600">{{ \Illuminate\Support\Str::limit($note->content, 320) }}</p>
                    @else
                        <p class="mt-3 text-sm italic text-gray-400">No note content yet.</p>
                    @endif
                    <p class="mt-5 text-xs text-gray-400">Updated {{ $note->updated_at->diffForHumans() }}</p>
                </article>
            @endforeach
        </div>
    @else
        <div class="rounded-2xl border border-dashed border-gray-300 bg-white px-6 py-14 text-center">
            <div class="mx-auto flex size-12 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600">
                <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M7 3.75h7l5 5v11.5H7a2 2 0 0 1-2-2v-12.5a2 2 0 0 1 2-2Z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M14 3.75v5h5M8.5 13h7M8.5 16.5h7" />
                </svg>
            </div>
            <h2 class="mt-4 text-sm font-semibold text-gray-900">{{ trim($search) === '' ? 'No notes yet' : 'No matching notes' }}</h2>
            <p class="mt-1 text-sm text-gray-500">{{ trim($search) === '' ? 'Your notes will appear here.' : 'Try another note title.' }}</p>
        </div>
    @endif
</section>
