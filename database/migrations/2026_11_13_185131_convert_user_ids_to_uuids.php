<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! in_array(Schema::getColumnType('users', 'id'), ['bigint', 'integer', 'int'], true)) {
            return;
        }

        $userIdMap = [];

        Schema::table('users', function (Blueprint $table): void {
            $table->uuid('hive_uuid')->nullable();
        });

        foreach (DB::table('users')->select('id')->orderBy('id')->get() as $user) {
            $newId = (string) Str::uuid();
            $userIdMap[(string) $user->id] = $newId;

            DB::table('users')->where('id', $user->id)->update(['hive_uuid' => $newId]);
        }

        $userTables = ['projects', 'notes', 'role_user', 'labels', 'sessions'];

        foreach ($userTables as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->uuid('hive_user_uuid')->nullable();
            });

            foreach (DB::table($tableName)->select('user_id')->whereNotNull('user_id')->distinct()->get() as $row) {
                $newUserId = $userIdMap[(string) $row->user_id] ?? null;

                if ($newUserId === null && $tableName !== 'sessions') {
                    throw new RuntimeException("Cannot migrate orphaned {$tableName}.user_id value.");
                }

                DB::table($tableName)
                    ->where('user_id', $row->user_id)
                    ->update(['hive_user_uuid' => $newUserId]);
            }
        }

        foreach (['notes', 'role_user', 'labels'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->uuid('hive_user_uuid')->nullable(false)->change();
            });
        }

        Schema::table('projects', function (Blueprint $table): void {
            $table->dropForeign('projects_user_id_foreign');
            $table->dropColumn('user_id');
            $table->renameColumn('hive_user_uuid', 'user_id');
        });

        Schema::table('notes', function (Blueprint $table): void {
            $table->dropForeign('notes_user_id_foreign');
            $table->dropColumn('user_id');
            $table->renameColumn('hive_user_uuid', 'user_id');
        });

        Schema::table('role_user', function (Blueprint $table): void {
            $table->dropForeign('role_user_user_id_foreign');
            $table->dropPrimary();
            $table->dropColumn('user_id');
            $table->renameColumn('hive_user_uuid', 'user_id');
        });

        Schema::table('labels', function (Blueprint $table): void {
            $table->dropForeign('labels_user_id_foreign');
            $table->dropColumn('user_id');
            $table->renameColumn('hive_user_uuid', 'user_id');
        });

        Schema::table('sessions', function (Blueprint $table): void {
            $table->dropIndex('sessions_user_id_index');
            $table->dropColumn('user_id');
            $table->renameColumn('hive_user_uuid', 'user_id');
        });

        Schema::create('users_uuid_upgrade', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->string('theme_preference', 10)->default('light');
        });

        foreach (DB::table('users')->orderBy('id')->get() as $user) {
            DB::table('users_uuid_upgrade')->insert([
                'id' => $user->hive_uuid,
                'name' => $user->name,
                'email' => $user->email,
                'email_verified_at' => $user->email_verified_at,
                'password' => null,
                'remember_token' => $user->remember_token,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
                'theme_preference' => $user->theme_preference ?? 'light',
            ]);
        }

        Schema::drop('users');
        Schema::rename('users_uuid_upgrade', 'users');

        Schema::table('projects', function (Blueprint $table): void {
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete()->cascadeOnUpdate();
        });

        Schema::table('notes', function (Blueprint $table): void {
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete()->cascadeOnUpdate();
        });

        Schema::table('role_user', function (Blueprint $table): void {
            $table->primary(['user_id', 'role_id']);
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete()->cascadeOnUpdate();
        });

        Schema::table('labels', function (Blueprint $table): void {
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete()->cascadeOnUpdate();
        });

        Schema::table('sessions', function (Blueprint $table): void {
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        throw new RuntimeException('User UUID migration cannot be reversed without invalidating Central Hub identities.');
    }
};
