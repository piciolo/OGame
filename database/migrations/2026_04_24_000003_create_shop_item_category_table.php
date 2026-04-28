<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('shop_item_category', function (Blueprint $table) {
            $table->unsignedBigInteger('shop_item_id');
            $table->unsignedBigInteger('shop_category_id');

            $table->primary(['shop_item_id', 'shop_category_id']);
            $table->foreign('shop_item_id')->references('id')->on('shop_items')->cascadeOnDelete();
            $table->foreign('shop_category_id')->references('id')->on('shop_categories')->cascadeOnDelete();
            $table->index('shop_category_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shop_item_category');
    }
};
