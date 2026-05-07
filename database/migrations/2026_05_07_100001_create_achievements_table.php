<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('achievements', function (Blueprint $table) {
            $table->id();
            $table->string('machine_name', 64)->unique();          // es. 'progress_base', 'season_progress'
            $table->unsignedInteger('display_number');             // es. 1, 2, 3 (per "#1 - Progresso della base")
            $table->string('name_key', 128);                        // chiave lang (es. 't_ingame.achievements.name_progress_base')
            $table->string('description_key', 128);                 // chiave lang
            $table->string('category', 32)->default('base');        // 'base' | 'season' | 'secret'
            $table->boolean('is_secret')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index('display_number');
            $table->index('category');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('achievements');
    }
};
