<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('planet_boosts', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('planet_id');
            $table->unsignedInteger('user_id');
            $table->string('resource', 16); // metal | crystal | deuterium | energy
            $table->unsignedTinyInteger('percent_bonus');
            $table->timestamp('expires_at');
            $table->unsignedBigInteger('source_user_item_id')->nullable();
            $table->timestamps();

            $table->foreign('planet_id')->references('id')->on('planets')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();

            $table->index(['planet_id', 'expires_at']);
            $table->index(['resource', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('planet_boosts');
    }
};
