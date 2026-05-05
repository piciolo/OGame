<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ipi_chapter_rewards', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('chapter_id');
            $table->unsignedTinyInteger('resource_index'); // 0=metal, 1=crystal, 2=deuterium, 3=energy, 4=dark_matter
            $table->unsignedBigInteger('quantity');

            $table->foreign('chapter_id')->references('id')->on('ipi_chapters')->cascadeOnDelete();
            $table->index('chapter_id');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ipi_chapter_rewards');
    }
};
