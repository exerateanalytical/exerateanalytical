<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('polls', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('creator_id');
            $table->uuid('region_id')->nullable();
            $table->string('title', 180);
            $table->text('description')->nullable();
            $table->enum('visibility', ['public', 'regional', 'private'])->default('public');
            $table->enum('type', ['standard', 'ranked', 'weighted', 'premium'])->default('standard');
            $table->json('options');
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->boolean('allow_multiple_votes')->default(false);
            $table->boolean('verified_only')->default(false);
            $table->unsignedInteger('total_votes')->default(0);
            $table->enum('status', ['active', 'closed', 'under_review', 'restricted'])->default('active');
            $table->timestamps();

            $table->foreign('creator_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('region_id')->references('id')->on('federation_regions')->nullOnDelete();

            $table->index(['region_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('polls');
    }
};
