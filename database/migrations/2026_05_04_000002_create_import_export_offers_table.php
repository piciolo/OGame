<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('import_export_offers', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id', false, true)->index();
            $table->unsignedBigInteger('item_id');
            $table->unsignedBigInteger('price');
            $table->string('status', 20)->default('pending')->index();
            $table->unsignedTinyInteger('change_count')->default(0);
            $table->timestamp('revealed_at')->nullable();
            $table->timestamp('consumed_at')->nullable();
            $table->timestamp('expires_at')->index();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('item_id')->references('id')->on('import_export_items');
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_export_offers');
    }
};
