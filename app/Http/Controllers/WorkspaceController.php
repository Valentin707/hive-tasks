<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Board;
use App\Models\Project;
use Illuminate\View\View;

class WorkspaceController extends Controller
{
    public function index(): View
    {
        return view('projects.index');
    }

    public function showBoard(Project $project, Board $board): View
    {
        abort_unless((int) $board->project_id === (int) $project->getKey(), 404);

        return view('projects.board', compact('project', 'board'));
    }
}
