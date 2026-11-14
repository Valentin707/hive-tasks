<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasIndex('users', 'users_uuid_upgrade_email_unique')) {
            return;
        }

        Schema::table('users', function (Blueprint $table): void {
            $table->dropUnique('users_uuid_upgrade_email_unique');
            $table->unique('email', 'users_email_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasIndex('users', 'users_email_unique')) {
            return;
        }

        Schema::table('users', function (Blueprint $table): void {
            $table->dropUnique('users_email_unique');
            $table->unique('email', 'users_uuid_upgrade_email_unique');
        });
    }
};
