<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('player_unlocked_avatars', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('player_id');
            $table->string('avatar_machine_name', 64);
            $table->timestamp('unlocked_at')->nullable();
            $table->timestamps();
            $table->unique(['player_id', 'avatar_machine_name'], 'pua_unique');
        });

        Schema::create('player_unlocked_skins', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('player_id');
            $table->string('skin_machine_name', 64);
            $table->timestamp('unlocked_at')->nullable();
            $table->timestamps();
            $table->unique(['player_id', 'skin_machine_name'], 'pus_unique');
        });

        Schema::create('player_unlocked_titles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('player_id');
            $table->string('title_machine_name', 64);
            $table->timestamp('unlocked_at')->nullable();
            $table->timestamps();
            $table->unique(['player_id', 'title_machine_name'], 'put_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('player_unlocked_titles');
        Schema::dropIfExists('player_unlocked_skins');
        Schema::dropIfExists('player_unlocked_avatars');
    }
};
