<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('auction_lot_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('tier', 16)->index();
            $table->string('lot_type', 32);
            $table->json('lot_payload');
            $table->string('lot_title');
            $table->string('lot_image', 128)->nullable();
            $table->unsignedInteger('weight')->default(10);
            $table->unsignedBigInteger('min_bid_points')->default(1000);
            $table->boolean('enabled')->default(true)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auction_lot_templates');
    }
};
