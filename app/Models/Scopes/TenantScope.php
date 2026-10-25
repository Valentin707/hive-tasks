<?php

declare(strict_types=1);

namespace App\Models\Scopes;

use App\Models\Board;
use App\Models\Column;
use App\Models\Note;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

final class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $userId = Auth::id();

        if ($userId === null) {
            return;
        }

        if ($model instanceof Project || $model instanceof Note) {
            $builder->where($model->qualifyColumn('user_id'), $userId);

            return;
        }

        $ownerRelation = match (true) {
            $model instanceof Board => 'project',
            $model instanceof Column => 'board.project',
            $model instanceof Task => 'column.board.project',
            default => null,
        };

        if ($ownerRelation !== null) {
            $builder->whereHas(
                $ownerRelation,
                static fn (Builder $query): Builder => $query->where('user_id', $userId),
            );
        }
    }
}
