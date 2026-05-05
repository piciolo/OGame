<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Adds the `enabled` flag on ipi_chapters and swaps Cap VI (4006, lifeform-only) and
 * Cap VII (4007) so the UI displays only 6 chapters until lifeform is implemented.
 *
 * After this migration:
 *   - Cap I..V keep sort_order 0..4 (display "I" to "V")
 *   - Cap VII (4007) → sort_order 5 (display "VI"), enabled=true
 *   - Cap VI  (4006) → sort_order 6 (would display "VII"), enabled=false (HIDDEN)
 *
 * To re-enable lifeform later: UPDATE ipi_chapters SET enabled=1, sort_order=5 WHERE id=4006;
 *                              UPDATE ipi_chapters SET sort_order=6 WHERE id=4007;
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::table('ipi_chapters', function (Blueprint $table) {
            $table->boolean('enabled')->default(true)->after('title');
        });

        // Swap sort_order and disable Cap VI
        DB::table('ipi_chapters')->where('id', 4007)->update(['sort_order' => 5, 'enabled' => true]);
        DB::table('ipi_chapters')->where('id', 4006)->update(['sort_order' => 6, 'enabled' => false]);
    }

    public function down(): void
    {
        DB::table('ipi_chapters')->where('id', 4006)->update(['sort_order' => 5, 'enabled' => true]);
        DB::table('ipi_chapters')->where('id', 4007)->update(['sort_order' => 6, 'enabled' => true]);

        Schema::table('ipi_chapters', function (Blueprint $table) {
            $table->dropColumn('enabled');
        });
    }
};
