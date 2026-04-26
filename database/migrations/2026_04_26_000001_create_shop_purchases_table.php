<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('shop_purchases', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('user_id');
            $table->unsignedBigInteger('shop_item_id');
            $table->unsignedBigInteger('user_item_id')->nullable();
            $table->unsignedInteger('dm_spent');
            $table->string('item_name', 100);
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('shop_item_id')->references('id')->on('shop_items')->cascadeOnDelete();
            $table->index(['user_id', 'created_at']);
            $table->index(['shop_item_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shop_purchases');
    }
};
