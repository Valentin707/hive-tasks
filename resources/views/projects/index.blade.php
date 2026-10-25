@extends('layouts.app')

@section('title', 'Projects')

@section('content')
    <div class="mb-8">
        <p class="mb-2 text-sm font-medium text-indigo-600">Workspace</p>
        <h1 class="text-2xl font-semibold tracking-tight text-gray-900 sm:text-3xl">Projects</h1>
        <p class="mt-2 text-sm text-gray-500">Choose a project board to continue working.</p>
    </div>

    @forelse ($projects as $project)
        <section class="mb-5 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm sm:p-6" aria-labelledby="project-{{ $project->id }}-title">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div class="min-w-0">
                    <h2 id="project-{{ $project->id }}-title" class="truncate text-lg font-semibold text-gray-900">{{ $project->name }}</h2>
                    @if ($project->description)
                        <p class="mt-1 max-w-2xl text-sm leading-6 text-gray-500">{{ $project->description }}</p>
                    @endif
                </div>
                <span class="inline-flex w-fit shrink-0 items-center rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-600">
                    {{ $project->boards_count }} {{ \Illuminate\Support\Str::plural('board', $project->boards_count) }}
                </span>
            </div>

            @if ($project->boards->isNotEmpty())
                <div class="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                    @foreach ($project->boards as $board)
                        <a
                            href="{{ route('projects.board', ['project' => $project, 'board' => $board]) }}"
                            class="group flex items-center justify-between gap-4 rounded-xl border border-gray-200 p-4 transition duration-150 hover:border-indigo-300 hover:bg-indigo-50/50"
                        >
                            <div class="flex min-w-0 items-center gap-3">
                                <span class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600 transition group-hover:bg-indigo-100">
                                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                        <rect x="3.75" y="4.75" width="7" height="6.5" rx="1.5" />
                                        <rect x="13.25" y="4.75" width="7" height="14.5" rx="1.5" />
                                        <rect x="3.75" y="14.25" width="7" height="5" rx="1.5" />
                                    </svg>
                                </span>
                                <span class="truncate text-sm font-medium text-gray-800">{{ $board->name }}</span>
                            </div>
                            <svg class="size-4 shrink-0 text-gray-400 transition group-hover:translate-x-0.5 group-hover:text-indigo-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m9.5 18 6-6-6-6" />
                            </svg>
                        </a>
                    @endforeach
                </div>
            @else
                <p class="mt-5 rounded-xl border border-dashed border-gray-300 bg-gray-50 px-4 py-5 text-sm text-gray-500">This project does not have any boards yet.</p>
            @endif
        </section>
    @empty
        <div class="rounded-2xl border border-dashed border-gray-300 bg-white px-6 py-14 text-center">
            <div class="mx-auto flex size-12 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600">
                <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <rect x="3.75" y="4.75" width="7" height="6.5" rx="1.5" />
                    <rect x="13.25" y="4.75" width="7" height="14.5" rx="1.5" />
                    <rect x="3.75" y="14.25" width="7" height="5" rx="1.5" />
                </svg>
            </div>
            <h2 class="mt-4 text-sm font-semibold text-gray-900">No projects yet</h2>
            <p class="mt-1 text-sm text-gray-500">Create a project and its boards will appear here.</p>
        </div>
    @endforelse
@endsection
