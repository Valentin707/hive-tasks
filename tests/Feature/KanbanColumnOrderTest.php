<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\KanbanBoard;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class KanbanColumnOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_persists_a_reordered_column_sequence_for_the_active_board(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $project = Project::query()->create([
            'name' => 'Reorder test project',
            'description' => null,
        ]);
        $board = $project->boards()->create(['name' => 'Main Board']);

        $columns = collect(['Todo', 'In Progress', 'Done', 'Deployed'])
            ->map(fn (string $name, int $index) => $board->columns()->create([
                'name' => $name,
                'position' => $index + 1,
            ]));

        Livewire::test(KanbanBoard::class, [
            'projectId' => (int) $project->getKey(),
            'boardId' => (int) $board->getKey(),
        ])
            ->call('updateColumnOrder', (int) $columns[3]->getKey(), 0)
            ->assertHasNoErrors();

        self::assertSame(
            [$columns[3]->getKey(), $columns[0]->getKey(), $columns[1]->getKey(), $columns[2]->getKey()],
            $board->columns()->orderBy('position')->pluck('id')->all(),
        );

        self::assertSame([1, 2, 3, 4], $board->columns()->orderBy('position')->pluck('position')->all());
    }
}
