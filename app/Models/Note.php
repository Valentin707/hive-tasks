<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\TenantOwned;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['title', 'content'])]
class Note extends Model
{
    use TenantOwned;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
