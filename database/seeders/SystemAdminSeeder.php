<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SystemAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            $administratorRole = Role::query()->updateOrCreate(
                ['slug' => Role::ADMIN_SLUG],
                ['name' => 'Administrator'],
            );
            Role::query()->updateOrCreate(
                ['slug' => Role::PROJECT_OWNER_SLUG],
                ['name' => 'Project Owner'],
            );

            $administrator = User::query()->firstOrNew([
                'email' => 'admin@tasks.hive',
            ]);

            if (! $administrator->exists) {
                $administrator->name = 'HiveTasks Administrator';
            }

            $administrator->password = null;
            $administrator->save();
            $administrator->roles()->syncWithoutDetaching([$administratorRole->getKey()]);
        });
    }
}
