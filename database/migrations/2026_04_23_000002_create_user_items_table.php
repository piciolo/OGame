<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('user_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('user_id')->index();
            $table->string('item_type', 64)->index();
            $table->string('tier', 16)->nullable()->index();
            $table->string('category', 32)->index();
            $table->string('activation_type', 16)->default('instant');
            $table->json('payload')->nullable();
            $table->string('status', 16)->default('available')->index();
            $table->timestamp('acquired_at');
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('consumed_at')->nullable();
            $table->string('source', 16)->default('auctioneer');
            $table->unsignedBigInteger('source_ref')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'item_type', 'tier', 'status']);
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_items');
    }
};
