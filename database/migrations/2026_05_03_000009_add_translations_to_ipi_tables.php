<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds JSON translation columns to IPI catalog tables. Each column maps locale codes
 * to localized strings, e.g. {"de": "Direktive I", "fr": "Directive I", ...}.
 * The original singular columns (title, description, text) keep the default Italian
 * value as a fallback when a locale is missing.
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::table('ipi_chapters', function (Blueprint $table) {
            $table->json('title_translations')->nullable()->after('title');
        });
        Schema::table('ipi_tasks', function (Blueprint $table) {
            $table->json('title_translations')->nullable()->after('title');
            $table->json('description_translations')->nullable()->after('description');
        });
        Schema::table('ipi_task_actions', function (Blueprint $table) {
            $table->json('text_translations')->nullable()->after('text');
        });
    }

    public function down(): void
    {
        Schema::table('ipi_chapters', function (Blueprint $table) {
            $table->dropColumn('title_translations');
        });
        Schema::table('ipi_tasks', function (Blueprint $table) {
            $table->dropColumn(['title_translations', 'description_translations']);
        });
        Schema::table('ipi_task_actions', function (Blueprint $table) {
            $table->dropColumn('text_translations');
        });
    }
};
