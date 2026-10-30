<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SystemAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            $administrator = User::query()->firstOrNew([
                'email' => 'admin@tasks.hive',
            ]);

            if (! $administrator->exists) {
                $password = config('system.admin_seed_password');

                if (! is_string($password) || mb_strlen($password) < 16) {
                    throw new RuntimeException(
                        'Set SYSTEM_ADMIN_PASSWORD to a unique password of at least 16 characters before seeding the system administrator.',
                    );
                }

                $administrator->name = 'HiveTasks Administrator';
                $administrator->password = $password;
            }

            $administrator->role = User::ROLE_ADMIN;
            $administrator->save();

            SystemSetting::query()->updateOrCreate(
                ['key' => SystemSetting::REGISTRATION_ENABLED],
                ['value' => 'true'],
            );
        });
    }
}
