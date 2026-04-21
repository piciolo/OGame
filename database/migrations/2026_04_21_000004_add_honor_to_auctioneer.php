<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'honor_points')) {
                $table->unsignedBigInteger('honor_points')->default(0)->after('dark_matter');
            }
        });

        Schema::table('auction_bids', function (Blueprint $table) {
            if (!Schema::hasColumn('auction_bids', 'honor')) {
                $table->unsignedBigInteger('honor')->default(0)->after('deuterium');
            }
        });

        Schema::table('auction_bids', function (Blueprint $table) {
            // Honor-only bids have no planet; allow NULL.
            $table->unsignedInteger('planet_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('auction_bids', function (Blueprint $table) {
            $table->unsignedInteger('planet_id')->nullable(false)->change();
        });

        Schema::table('auction_bids', function (Blueprint $table) {
            if (Schema::hasColumn('auction_bids', 'honor')) {
                $table->dropColumn('honor');
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'honor_points')) {
                $table->dropColumn('honor_points');
            }
        });
    }
};
