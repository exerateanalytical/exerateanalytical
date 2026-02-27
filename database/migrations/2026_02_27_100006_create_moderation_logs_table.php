<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('moderation_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('moderator_id');
            $table->uuid('subject_id');
            $table->string('subject_type');
            $table->enum('action', ['flagged', 'restricted', 'restored', 'removed']);
            $table->text('reason')->nullable();
            $table->timestamps();

            $table->foreign('moderator_id')->references('id')->on('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('moderation_logs');
    }
};
