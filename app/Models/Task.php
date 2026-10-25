<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Task extends Model
{
    protected $fillable = ['column_id', 'title', 'description', 'position'];

    public function column(): BelongsTo
    {
        return $this->belongsTo(Column::class);
    }
}
