<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('chat_reports', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('chat_message_id');
            $table->foreign('chat_message_id')->references('id')->on('chat_messages')->onDelete('cascade');
            $table->integer('reporter_user_id', false, true);
            $table->foreign('reporter_user_id')->references('id')->on('users')->onDelete('cascade');
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            // A given user can only report a given message once.
            $table->unique(['chat_message_id', 'reporter_user_id']);
            $table->index('reviewed_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_reports');
    }
};
