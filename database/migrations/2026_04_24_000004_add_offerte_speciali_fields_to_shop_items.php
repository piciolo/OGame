<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('shop_items', function (Blueprint $table) {
            $table->text('extended_description')->nullable()->after('description');
            $table->text('effect_description')->nullable()->after('extended_description');
            $table->unsignedInteger('price_dm_original')->nullable()->after('price_dm');
            $table->string('price_label_original', 32)->nullable()->after('price_label');
            $table->string('image_fallback', 128)->nullable()->after('image');
            $table->boolean('is_lifeform')->default(false)->after('image_fallback');
            $table->string('booster_type', 32)->nullable()->after('is_lifeform')->index();
            $table->string('tier_key', 32)->nullable()->after('booster_type');
        });
    }

    public function down(): void
    {
        Schema::table('shop_items', function (Blueprint $table) {
            $table->dropColumn([
                'extended_description', 'effect_description',
                'price_dm_original', 'price_label_original',
                'image_fallback', 'is_lifeform',
                'booster_type', 'tier_key',
            ]);
        });
    }
};
