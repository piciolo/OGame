<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Render reward_type / reward_machine_name nullable on achievement_tiers.
 *
 * In OGame ufficiale ~36% dei tier non ha ricompense (solo le tier intermedie
 * o finali sbloccano avatar/skin/titoli). Lo scaffold iniziale assegnava un
 * placeholder fittizio per evitare NOT NULL: ora li rappresentiamo con NULL,
 * fedelmente all'autoritativo (_research/achievements_authoritative.tsv).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('achievement_tiers', function (Blueprint $table) {
            $table->string('reward_type', 16)->nullable()->change();
            $table->string('reward_machine_name', 64)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('achievement_tiers', function (Blueprint $table) {
            $table->string('reward_type', 16)->nullable(false)->change();
            $table->string('reward_machine_name', 64)->nullable(false)->change();
        });
    }
};
