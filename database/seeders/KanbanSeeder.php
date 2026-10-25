<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use App\Models\Project;
use Illuminate\Database\Seeder;

class KanbanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::query()->firstOrCreate(
            ['email' => 'test@example.com'],
            ['name' => 'Test User', 'password' => 'password'],
        );

        $project = $user->projects()->firstOrCreate(
            ['name' => 'My First Project'],
            ['description' => 'A sample project for HiveTasks.'],
        );

        $board = $project->boards()->firstOrCreate(['name' => 'Default Board']);

        $columns = [
            'Todo' => 1,
            'In Progress' => 2,
            'Done' => 3,
            'Deployed' => 4,
        ];

        foreach ($columns as $name => $position) {
            $column = $board->columns()->firstOrCreate(
                ['name' => $name],
                ['position' => $position],
            );

            if ($name === 'Todo') {
                $tasks = [
                    'Create the first project plan',
                    'Sketch the Kanban board layout',
                ];
            } elseif ($name === 'In Progress') {
                $tasks = [
                    'Build the task data model',
                    'Add sample board content',
                ];
            } else {
                $tasks = [];
            }

            foreach ($tasks as $taskPosition => $title) {
                $column->tasks()->firstOrCreate(
                    ['title' => $title],
                    ['position' => $taskPosition + 1],
                );
            }
        }
    }
}
