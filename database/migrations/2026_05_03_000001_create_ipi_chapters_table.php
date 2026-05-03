<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ipi_chapters', function (Blueprint $table) {
            $table->unsignedInteger('id')->primary();   // 4001..4007 (matches OGame chapter IDs)
            $table->unsignedSmallInteger('sort_order');
            $table->string('title', 128);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ipi_chapters');
    }
};
