<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\TenantOwned;
use App\Models\Scopes\TenantScope;
use Database\Factories\LabelFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['name', 'color_code'])]
class Label extends Model
{
    /** @var array<string, string> */
    public const array DEFAULT_LABELS = [
        'support' => '#3b82f6',
        'bug' => '#ef4444',
        'feature' => '#22c55e',
        'task' => '#8b5cf6',
        'spike' => '#f59e0b',
    ];

    /** @use HasFactory<LabelFactory> */
    use HasFactory;

    use TenantOwned;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tasks(): BelongsToMany
    {
        return $this->belongsToMany(Task::class);
    }

    public static function ensureDefaultLabelsFor(User $user): void
    {
        static::withoutEvents(static function () use ($user): void {
            $existingNames = static::query()
                ->withoutGlobalScope(TenantScope::class)
                ->where('user_id', $user->getKey())
                ->whereIn('name', array_keys(self::DEFAULT_LABELS))
                ->pluck('name')
                ->all();
            $existingLabels = array_fill_keys($existingNames, true);

            foreach (self::DEFAULT_LABELS as $name => $colorCode) {
                if (isset($existingLabels[$name])) {
                    continue;
                }

                $label = new self;
                $label->setAttribute('user_id', $user->getKey());
                $label->fill([
                    'name' => $name,
                    'color_code' => $colorCode,
                ]);
                $label->save();
            }
        });
    }
}
