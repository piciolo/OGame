<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('ipi_player_chapter_collected', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id', false, true);
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->integer('chapter_id', false, true);
            $table->foreign('chapter_id')->references('id')->on('ipi_chapters')->onDelete('cascade');
            $table->timestamp('collected_at');
            $table->timestamps();

            $table->unique(['user_id', 'chapter_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ipi_player_chapter_collected');
    }
};
