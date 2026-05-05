<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-player progress on IPI tasks.
 * Pattern matches existing project migrations (see 2024_10_28_create_highscore_table).
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::create('ipi_player_progress', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id', false, true);
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->integer('task_id', false, true);
            $table->foreign('task_id')->references('id')->on('ipi_tasks')->onDelete('cascade');
            $table->string('state', 16)->default('none');
            $table->unsignedSmallInteger('progress_count')->default(0);
            $table->timestamp('tracked_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('collected_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'task_id']);
            $table->index(['user_id', 'state']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ipi_player_progress');
    }
};
