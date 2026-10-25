@extends('layouts.app')

@section('title', $board->name)

@section('content')
    <div class="mb-6">
        <a href="{{ route('projects.index') }}" class="inline-flex items-center gap-2 text-sm font-medium text-gray-500 transition hover:text-indigo-600">
            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="m14.5 18-6-6 6-6" />
            </svg>
            All projects
        </a>
    </div>

    <livewire:kanban-board :project-id="$project->id" :board-id="$board->id" />
@endsection
