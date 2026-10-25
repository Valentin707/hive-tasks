<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table): void {
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
        });

        $userCount = DB::table('users')->count();

        if ($userCount === 1) {
            $userId = DB::table('users')->value('id');

            DB::table('projects')
                ->whereNull('user_id')
                ->update(['user_id' => $userId]);
        }

        Schema::create('notes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title')->default('Untitled note');
            $table->longText('content')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notes');

        Schema::table('projects', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('user_id');
        });
    }
};
