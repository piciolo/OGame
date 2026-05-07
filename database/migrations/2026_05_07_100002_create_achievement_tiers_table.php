<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('achievement_tiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('achievement_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('tier');                   // 1..5
            $table->unsignedBigInteger('target');                  // target di completamento (es. 10, 100, 1000)
            $table->string('reward_type', 16);                     // 'avatar' | 'skin' | 'title'
            $table->string('reward_machine_name', 64);             // es. 'A1_T1_Pskin_ID1'
            $table->timestamps();

            $table->unique(['achievement_id', 'tier'], 'achievement_tier_unique');
            $table->index('reward_machine_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('achievement_tiers');
    }
};
