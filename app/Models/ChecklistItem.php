<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasTenantScope;
use Database\Factories\ChecklistItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChecklistItem extends Model
{
    /** @use HasFactory<ChecklistItemFactory> */
    use HasFactory;

    use HasTenantScope;

    protected $fillable = ['task_id', 'title', 'is_completed', 'position'];

    protected function casts(): array
    {
        return [
            'is_completed' => 'boolean',
        ];
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }
}
