<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Label;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Seeder;

class LabelSeeder extends Seeder
{
    /** @var array<string, string> */
    private const SAMPLE_LABELS = [
        'Priority' => '#ef4444',
        'Design' => '#8b5cf6',
        'Backend' => '#3b82f6',
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::query()->chunkById(100, function (Collection $users): void {
            foreach ($users as $user) {
                $labels = [];

                foreach (self::SAMPLE_LABELS as $name => $colorCode) {
                    $label = Label::query()
                        ->where('user_id', $user->getKey())
                        ->where('name', $name)
                        ->first();

                    if ($label === null) {
                        $label = new Label;
                        $label->user_id = $user->getKey();
                        $label->name = $name;
                        $label->color_code = $colorCode;
                        $label->save();
                    }

                    $labels[] = $label;
                }

                Task::query()
                    ->whereHas('column.board.project', static fn (Builder $query): Builder => $query->where('user_id', $user->getKey()))
                    ->orderBy('id')
                    ->get()
                    ->each(static function (Task $task, int $index) use ($labels): void {
                        $task->labels()->syncWithoutDetaching([
                            $labels[$index % count($labels)]->getKey(),
                        ]);
                    });
            }
        });
    }
}
