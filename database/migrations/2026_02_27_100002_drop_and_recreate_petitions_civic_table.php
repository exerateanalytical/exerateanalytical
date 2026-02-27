<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop old country-based petition_signatures first (FK dependency)
        Schema::dropIfExists('petition_signatures');
        // Drop old country-based petitions table
        Schema::dropIfExists('petitions');

        Schema::create('petitions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('creator_id');
            $table->uuid('region_id')->nullable();
            $table->string('title', 180);
            $table->text('summary');
            $table->longText('body');
            $table->unsignedInteger('signature_goal')->default(100);
            $table->unsignedInteger('signature_count')->default(0);
            $table->enum('status', [
                'active',
                'milestone_reached',
                'submitted',
                'closed',
                'under_review',
                'restricted',
            ])->default('active');
            $table->timestamp('deadline')->nullable();
            $table->timestamps();

            $table->foreign('creator_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('region_id')->references('id')->on('federation_regions')->nullOnDelete();

            $table->index(['region_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('petitions');
    }
};
