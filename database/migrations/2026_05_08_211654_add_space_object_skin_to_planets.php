<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Aggiunge il campo `space_object_skin` alla tabella planets.
 *
 * Quando il giocatore seleziona una skin pianeta sbloccata dal catalogo Trofei,
 * la skin viene applicata a UN pianeta specifico (replica OGame). La sidebar
 * planet bar leggerà questo campo per sostituire l'immagine del pianeta default
 * con quella della skin selezionata.
 *
 * NULL = pianeta usa l'immagine biome/type calcolata dalle coordinate (default).
 * Stringa = machine_name della skin (es. A1_T1_Pskin_ID1).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('planets', function (Blueprint $table) {
            $table->string('space_object_skin', 64)->nullable()->after('planet_type');
        });
    }

    public function down(): void
    {
        Schema::table('planets', function (Blueprint $table) {
            $table->dropColumn('space_object_skin');
        });
    }
};
