<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('poll_templates', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('title', 180);
            $table->text('description')->nullable();

            // civic | policy | election | feedback | community | general
            $table->string('category', 40)->default('general');

            // Mirrors the Poll.type enum
            $table->enum('poll_type', ['standard', 'ranked', 'weighted', 'premium'])
                ->default('standard');

            // Pre-filled answer options (array of strings)
            $table->json('options');

            $table->boolean('allow_multiple_votes')->default(false);
            $table->boolean('verified_only')->default(false);
            $table->boolean('is_premium')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);

            // Null for system-seeded templates
            $table->foreignUuid('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->index(['is_premium', 'category']);
            $table->index('sort_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('poll_templates');
    }
};
