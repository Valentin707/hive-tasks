<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Task;
use Illuminate\Database\Seeder;

class ChecklistItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $task = Task::query()->where('title', 'Create the first project plan')->first();

        if ($task === null) {
            return;
        }

        foreach ([
            'Define project goals',
            'Break work into milestones',
            'Review the plan with the team',
        ] as $position => $title) {
            $task->checklists()->firstOrCreate(
                ['title' => $title],
                ['position' => $position],
            );
        }
    }
}
