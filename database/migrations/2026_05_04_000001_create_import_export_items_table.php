<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('import_export_items', function (Blueprint $table) {
            $table->id();
            $table->char('ref', 40)->unique();
            $table->string('category', 20)->index();
            $table->string('type', 20)->index();
            $table->string('rarity', 10)->index();
            $table->string('name', 100);
            $table->text('description');
            $table->integer('effect_value');
            $table->integer('duration_seconds')->nullable();
            $table->string('icon_path', 255);
            $table->integer('drop_weight');
            $table->integer('change_dm_cost');
            $table->integer('price_base');
            $table->json('translations')->nullable();
            $table->timestamps();

            $table->index(['category', 'rarity']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_export_items');
    }
};
