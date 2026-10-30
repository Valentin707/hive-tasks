<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['name', 'slug'])]
class Role extends Model
{
    public const ADMIN_SLUG = 'admin';

    public const PROJECT_OWNER_SLUG = 'po';

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }
}
