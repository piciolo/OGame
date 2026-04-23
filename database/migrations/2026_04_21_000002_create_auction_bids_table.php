<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('auction_bids', function (Blueprint $table) {
            $table->id();
            $table->foreignId('auction_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('user_id')->index();
            $table->unsignedInteger('planet_id');

            $table->unsignedBigInteger('metal')->default(0);
            $table->unsignedBigInteger('crystal')->default(0);
            $table->unsignedBigInteger('deuterium')->default(0);
            $table->unsignedBigInteger('points');
            $table->unsignedBigInteger('total_points_after');

            $table->timestamp('placed_at')->useCurrent();
            $table->timestamps();

            $table->index(['auction_id', 'placed_at']);
            $table->index(['auction_id', 'user_id']);

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('planet_id')->references('id')->on('planets')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auction_bids');
    }
};
