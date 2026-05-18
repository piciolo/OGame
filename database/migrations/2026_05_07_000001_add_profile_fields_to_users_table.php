<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'profile_visible')) {
                $table->boolean('profile_visible')->default(true);
            }
            if (!Schema::hasColumn('users', 'achievements_visible')) {
                $table->boolean('achievements_visible')->default(true);
            }
            if (!Schema::hasColumn('users', 'global_profile')) {
                $table->boolean('global_profile')->default(false);
            }
            if (!Schema::hasColumn('users', 'profile_title')) {
                $table->string('profile_title', 50)->nullable();
            }
            if (!Schema::hasColumn('users', 'profile_tags')) {
                $table->json('profile_tags')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['profile_visible', 'achievements_visible', 'global_profile', 'profile_title', 'profile_tags']);
        });
    }
};
