<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ipi_tasks', function (Blueprint $table) {
            $table->unsignedInteger('id')->primary();   // 5001..5070 (matches OGame task IDs)
            $table->unsignedInteger('chapter_id');
            $table->unsignedSmallInteger('sort_order');
            $table->string('title', 255);
            $table->text('description');
            $table->string('image', 64);                // basename, e.g. "task5001.jpg"
            $table->unsignedSmallInteger('total_steps');

            $table->foreign('chapter_id')->references('id')->on('ipi_chapters')->cascadeOnDelete();
            $table->index(['chapter_id', 'sort_order']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ipi_tasks');
    }
};
