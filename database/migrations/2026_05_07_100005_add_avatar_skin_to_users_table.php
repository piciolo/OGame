<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'profile_avatar')) {
                $table->string('profile_avatar', 64)->nullable();
            }
            if (!Schema::hasColumn('users', 'profile_planet_skin')) {
                $table->string('profile_planet_skin', 64)->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'profile_avatar')) {
                $table->dropColumn('profile_avatar');
            }
            if (Schema::hasColumn('users', 'profile_planet_skin')) {
                $table->dropColumn('profile_planet_skin');
            }
        });
    }
};
