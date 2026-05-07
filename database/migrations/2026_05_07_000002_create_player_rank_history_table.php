<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('player_rank_history', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('player_id');
            $table->unsignedTinyInteger('highscore_type'); // 0 general, 1 economy, 2 research, 3 military
            $table->unsignedInteger('rank');
            $table->unsignedBigInteger('points');
            $table->date('snapshot_date');
            $table->timestamps();

            $table->unique(['player_id', 'highscore_type', 'snapshot_date'], 'prh_unique_snapshot');
            $table->index(['player_id', 'highscore_type', 'snapshot_date'], 'prh_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('player_rank_history');
    }
};
