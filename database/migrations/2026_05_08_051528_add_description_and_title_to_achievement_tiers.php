<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Aggiunge campi per descrizione per-tier e testo titolo (ricompensa) inline.
 *
 * Sorgente: scraping diretto da OGame.it (D:/OGAME WEB/trofei.txt) — in OGame
 * ogni tier ha una descrizione propria (es. "Sviluppa una Miniera di Metallo
 * portandola al livello 20") e i titoli ricompensa sono testi finiti, non slug.
 *
 * Per ora salviamo solo IT (testo direttamente in DB). I18n cross-lingua è
 * un task futuro: gli altri lang potranno tradurre via lookup machine_name.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('achievement_tiers', function (Blueprint $table) {
            $table->text('description_text')->nullable()->after('target');
            $table->string('title_text', 64)->nullable()->after('reward_machine_name');
        });
    }

    public function down(): void
    {
        Schema::table('achievement_tiers', function (Blueprint $table) {
            $table->dropColumn(['description_text', 'title_text']);
        });
    }
};
