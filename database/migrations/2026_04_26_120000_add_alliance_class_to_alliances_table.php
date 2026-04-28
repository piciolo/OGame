<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds alliance class state to the `alliances` table.
 *
 * - alliance_class:           1=Warrior, 2=Trader, 3=Researcher (nullable = none)
 * - alliance_class_changed_at: timestamp of last change (used for cooldown / audit)
 * - alliance_class_free_used:  whether the founder has already used the
 *                              first-time-free activation
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::table('alliances', function (Blueprint $table) {
            $table->unsignedTinyInteger('alliance_class')->nullable()->after('newcomer_rank_name');
            $table->timestamp('alliance_class_changed_at')->nullable()->after('alliance_class');
            $table->boolean('alliance_class_free_used')->default(false)->after('alliance_class_changed_at');
        });
    }

    public function down(): void
    {
        Schema::table('alliances', function (Blueprint $table) {
            $table->dropColumn(['alliance_class', 'alliance_class_changed_at', 'alliance_class_free_used']);
        });
    }
};
