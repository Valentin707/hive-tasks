<?php

use App\Models\Board;
use App\Models\Project;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;

Route::view('/', 'dashboard')->name('dashboard');

Route::get('/projects', function (): View {
    $projects = Project::query()
        ->with(['boards' => fn (HasMany $query) => $query->orderBy('name')])
        ->withCount('boards')
        ->orderBy('name')
        ->get();

    return view('projects.index', compact('projects'));
})->name('projects.index');

Route::get('/projects/{project}/boards/{board}', function (Project $project, Board $board): View {
    abort_unless((int) $board->project_id === (int) $project->getKey(), 404);

    return view('projects.board', compact('project', 'board'));
})->name('projects.board');
