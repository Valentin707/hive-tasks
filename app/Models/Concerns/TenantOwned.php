<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

trait TenantOwned
{
    use HasTenantScope;

    protected static function bootTenantOwned(): void
    {
        static::creating(static function (Model $model): void {
            if (($userId = Auth::id()) !== null) {
                $model->setAttribute('user_id', $userId);
            }
        });

        static::updating(static function (Model $model): void {
            if ($model->isDirty('user_id')) {
                $model->setAttribute('user_id', $model->getOriginal('user_id'));
            }
        });
    }
}
