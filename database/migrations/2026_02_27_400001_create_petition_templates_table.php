<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('petition_templates', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('title', 180);
            $table->text('description')->nullable();

            // civic | policy | community | environment | rights | general
            $table->string('category', 40)->default('general');

            // Pre-filled body content
            $table->text('summary_template')->nullable();
            $table->text('body_template')->nullable();

            $table->unsignedInteger('default_signature_goal')->default(1000);
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
        Schema::dropIfExists('petition_templates');
    }
};
