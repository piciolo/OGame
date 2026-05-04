<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('import_export_history', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id', false, true)->index();
            $table->unsignedBigInteger('item_id');
            $table->string('acquisition_method', 20);
            $table->unsignedBigInteger('paid_metal')->default(0);
            $table->unsignedBigInteger('paid_crystal')->default(0);
            $table->unsignedBigInteger('paid_deuterium')->default(0);
            $table->unsignedBigInteger('paid_honor')->default(0);
            $table->unsignedInteger('paid_dm')->default(0);
            $table->unsignedInteger('source_planet_id')->nullable();
            $table->string('source_body_type', 10)->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('item_id')->references('id')->on('import_export_items');
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_export_history');
    }
};
