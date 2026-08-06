<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('shop_items', function (Blueprint $table) {
            $table->id();
            $table->string('ref', 64)->unique();
            $table->string('name', 128);
            $table->text('description')->nullable();
            $table->unsignedInteger('price_dm');
            $table->string('price_label', 32);
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->string('duration_label', 64)->nullable();
            $table->string('rarity', 16)->index();
            $table->string('image', 128);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shop_items');
    }
};
