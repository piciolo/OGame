<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('player_achievement_progress', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('player_id');
            $table->foreignId('achievement_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('current_value')->default(0);
            $table->unsignedTinyInteger('completed_tier')->default(0); // 0 = nessun tier completato
            $table->timestamp('last_updated_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['player_id', 'achievement_id'], 'pap_unique');
            $table->index(['player_id', 'completed_tier']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('player_achievement_progress');
    }
};
