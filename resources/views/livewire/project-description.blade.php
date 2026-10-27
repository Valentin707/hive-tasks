<section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm sm:p-6" aria-labelledby="project-description-title">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <p class="text-sm font-medium text-indigo-600">Project details</p>
            <h2 id="project-description-title" class="mt-1 text-lg font-semibold text-gray-900">Description</h2>
        </div>

        @if (! $isEditing)
            <button type="button" wire:click="startEditing" wire:loading.attr="disabled" wire:target="startEditing" class="button-secondary shrink-0">
                Edit description
            </button>
        @endif
    </div>

    @if ($isEditing)
        <form wire:submit="saveDescription" class="mt-6">
            <div>
                <p class="form-label">Project description</p>
                <div
                    wire:key="project-description-editor-{{ $projectId }}"
                    x-data="quillRichTextEditor({ placeholder: 'Add project details...', ariaLabel: 'Project description', minHeight: '16rem' })"
                    x-init="$nextTick(() => initialize())"
                >
                    <div class="quill-rich-text-editor" wire:ignore>
                        <div x-ref="editor"></div>
                    </div>
                    <textarea x-ref="contentInput" wire:model="description" aria-hidden="true" tabindex="-1" class="hidden">{{ $description }}</textarea>
                    <textarea x-ref="initialHtml" aria-hidden="true" tabindex="-1" class="hidden">{{ $descriptionHtml }}</textarea>
                </div>
                @error('description')
                    <p id="project-description-error-{{ $projectId }}" class="form-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="mt-5 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <button type="button" wire:click="cancelEditing" wire:loading.attr="disabled" wire:target="saveDescription" class="button-secondary">
                    Cancel
                </button>
                <button type="submit" wire:loading.attr="disabled" wire:target="saveDescription" class="button-primary">
                    <span wire:loading.remove wire:target="saveDescription">Save description</span>
                    <span wire:loading wire:target="saveDescription">Saving...</span>
                </button>
            </div>
        </form>
    @else
        <div class="mt-6 rounded-xl bg-gray-50 px-4 py-5 sm:px-5">
            @if ($descriptionHtml !== '')
                <div class="ql-editor quill-content-display">{!! $descriptionHtml !!}</div>
            @else
                <p class="text-sm leading-6 text-gray-500">No description has been added yet. Add a few details to give this project context.</p>
            @endif
        </div>
    @endif
</section>
