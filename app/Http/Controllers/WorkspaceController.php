<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Board;
use App\Models\Project;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\View\View;

class WorkspaceController extends Controller
{
    public function index(): View
    {
        $projects = Project::query()
            ->with(['boards' => fn (HasMany $boards) => $boards->orderBy('name')])
            ->withCount('boards')
            ->orderBy('name')
            ->get();

        return view('projects.index', compact('projects'));
    }

    public function showBoard(Project $project, Board $board): View
    {
        abort_unless((int) $board->project_id === (int) $project->getKey(), 404);

        return view('projects.board', compact('project', 'board'));
    }
}
