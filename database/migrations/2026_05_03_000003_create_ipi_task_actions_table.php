<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ipi_task_actions', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('task_id');
            $table->unsignedSmallInteger('sort_order');
            $table->text('text');                                // human-readable IT action label
            $table->string('requirement_type', 32)->nullable();  // building_level | research_level | ship_built | defense_built | production_setting | event | ...
            $table->string('requirement_target', 64)->nullable(); // machine_name (e.g. metal_mine) or symbolic event key
            $table->unsignedInteger('requirement_value')->nullable(); // level, qty, percentage
            $table->string('planet_scope', 32)->nullable();      // home_planet | colonized_planet | any
            $table->string('hint_target', 64)->nullable();       // ipiHintable[data-ipi-hint=...] (M4)

            $table->foreign('task_id')->references('id')->on('ipi_tasks')->cascadeOnDelete();
            $table->index(['task_id', 'sort_order']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ipi_task_actions');
    }
};
