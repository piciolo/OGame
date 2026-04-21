<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('auctions', function (Blueprint $table) {
            $table->id();
            $table->string('status', 16)->index();
            $table->string('tier', 16);
            $table->string('lot_type', 32);
            $table->json('lot_payload');
            $table->string('lot_title');
            $table->string('lot_image', 128)->nullable();

            $table->unsignedBigInteger('min_bid_points')->default(1000);
            $table->unsignedBigInteger('current_bid_points')->default(0);
            $table->unsignedInteger('current_bidder_user_id')->nullable()->index();
            $table->unsignedInteger('current_bidder_planet_id')->nullable();
            $table->string('current_bidder_name')->nullable();

            $table->timestamp('waiting_ends_at')->nullable()->index();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ends_at')->nullable()->index();
            $table->timestamp('closed_at')->nullable();
            $table->timestamp('assigned_at')->nullable();

            $table->unsignedInteger('extension_count')->default(0);
            $table->unsignedInteger('bid_count')->default(0);
            $table->timestamps();

            $table->foreign('current_bidder_user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('current_bidder_planet_id')->references('id')->on('planets')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auctions');
    }
};
