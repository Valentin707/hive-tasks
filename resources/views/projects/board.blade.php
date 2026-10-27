@extends('layouts.app')

@section('title', $project->name)

@section('content')
    <div
        x-data="{
            activeTab: window.location.hash === '#board' ? 'board' : 'description',
            selectTab(tab) {
                this.activeTab = tab;
                const url = new URL(window.location.href);
                url.hash = tab;
                window.history.replaceState(window.history.state, '', url);
            },
        }"
        class="space-y-4"
    >
        <header>
            <a href="{{ route('projects.index') }}" class="inline-flex items-center gap-2 text-sm font-medium text-gray-500 transition hover:text-indigo-600">
                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m14.5 18-6-6 6-6" />
                </svg>
                Back
            </a>
        </header>

        <div class="border-b border-gray-200">
            <div role="tablist" aria-label="Project sections" class="-mb-px flex gap-6">
                <button
                    id="project-description-tab"
                    type="button"
                    role="tab"
                    aria-controls="project-description-panel"
                    x-ref="descriptionTab"
                    x-bind:aria-selected="activeTab === 'description'"
                    x-bind:tabindex="activeTab === 'description' ? 0 : -1"
                    x-on:click="selectTab('description')"
                    x-on:keydown.arrow-left.prevent="selectTab('board'); $nextTick(() => $refs.boardTab.focus())"
                    x-on:keydown.arrow-right.prevent="selectTab('board'); $nextTick(() => $refs.boardTab.focus())"
                    x-on:keydown.home.prevent="selectTab('description'); $nextTick(() => $refs.descriptionTab.focus())"
                    x-on:keydown.end.prevent="selectTab('board'); $nextTick(() => $refs.boardTab.focus())"
                    class="border-b-2 px-1 pb-3 text-sm font-medium transition"
                    x-bind:class="activeTab === 'description' ? 'border-indigo-600 text-indigo-700' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'"
                >
                    Description
                </button>
                <button
                    id="project-board-tab"
                    type="button"
                    role="tab"
                    aria-controls="project-board-panel"
                    x-ref="boardTab"
                    x-bind:aria-selected="activeTab === 'board'"
                    x-bind:tabindex="activeTab === 'board' ? 0 : -1"
                    x-on:click="selectTab('board')"
                    x-on:keydown.arrow-right.prevent="selectTab('description'); $nextTick(() => $refs.descriptionTab.focus())"
                    x-on:keydown.arrow-left.prevent="selectTab('description'); $nextTick(() => $refs.descriptionTab.focus())"
                    x-on:keydown.home.prevent="selectTab('description'); $nextTick(() => $refs.descriptionTab.focus())"
                    x-on:keydown.end.prevent="selectTab('board'); $nextTick(() => $refs.boardTab.focus())"
                    class="border-b-2 px-1 pb-3 text-sm font-medium transition"
                    x-bind:class="activeTab === 'board' ? 'border-indigo-600 text-indigo-700' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'"
                >
                    Kanban board
                </button>
            </div>
        </div>

        <section id="project-description-panel" role="tabpanel" aria-labelledby="project-description-tab" tabindex="0" x-show="activeTab === 'description'" x-cloak>
            <livewire:project-description :project-id="$project->id" :key="'project-description-'.$project->id" />
        </section>

        <section id="project-board-panel" role="tabpanel" aria-labelledby="project-board-tab" tabindex="0" x-show="activeTab === 'board'" x-cloak>
            <livewire:kanban-board :project-id="$project->id" :board-id="$board->id" :key="'project-board-'.$project->id.'-'.$board->id" />
        </section>
    </div>
@endsection
