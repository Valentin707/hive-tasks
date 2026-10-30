<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $timestamp = now();

        DB::table('roles')->insertOrIgnore([
            ['name' => 'Administrator', 'slug' => 'admin', 'created_at' => $timestamp, 'updated_at' => $timestamp],
            ['name' => 'Project Owner', 'slug' => 'po', 'created_at' => $timestamp, 'updated_at' => $timestamp],
        ]);

        $administratorRoleId = (int) DB::table('roles')->where('slug', 'admin')->value('id');
        $projectOwnerRoleId = (int) DB::table('roles')->where('slug', 'po')->value('id');
        $hasLegacyRoleColumn = Schema::hasColumn('users', 'role');
        $userColumns = $hasLegacyRoleColumn ? ['id', 'role'] : ['id'];

        DB::table('users')
            ->select($userColumns)
            ->orderBy('id')
            ->chunkById(500, static function (Collection $users) use ($administratorRoleId, $projectOwnerRoleId, $hasLegacyRoleColumn): void {
                $roleAssignments = [];

                foreach ($users as $user) {
                    $legacyRole = $hasLegacyRoleColumn && is_string($user->role)
                        ? mb_strtolower(trim($user->role))
                        : '';
                    $roleId = $legacyRole === 'admin' ? $administratorRoleId : $projectOwnerRoleId;

                    $roleAssignments[] = [
                        'user_id' => (int) $user->id,
                        'role_id' => $roleId,
                    ];
                }

                DB::table('role_user')->insertOrIgnore($roleAssignments);
            });

        if ($hasLegacyRoleColumn) {
            Schema::table('users', function (Blueprint $table): void {
                $table->dropColumn('role');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('users', 'role')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->string('role')->default('PO');
            });
        }

        $administratorRoleId = DB::table('roles')->where('slug', 'admin')->value('id');

        if ($administratorRoleId !== null) {
            $administratorUserIds = DB::table('role_user')
                ->where('role_id', $administratorRoleId)
                ->select('user_id');

            DB::table('users')
                ->whereIn('id', $administratorUserIds)
                ->update(['role' => 'Admin']);
        }
    }
};
