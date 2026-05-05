<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ipi_task_rewards', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('task_id');
            $table->unsignedTinyInteger('resource_index'); // 0=metal, 1=crystal, 2=deuterium, 3=energy, 4=dark_matter (matches OGame's resource-N CSS class)
            $table->unsignedBigInteger('quantity');

            $table->foreign('task_id')->references('id')->on('ipi_tasks')->cascadeOnDelete();
            $table->index('task_id');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ipi_task_rewards');
    }
};
