<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('policy_proposals', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('creator_id');
            $table->uuid('region_id')->nullable();
            $table->string('title', 200);
            $table->longText('abstract');
            $table->longText('full_text');
            $table->enum('stage', [
                'draft',
                'public_consultation',
                'revision',
                'finalized',
                'archived',
            ])->default('draft');
            $table->enum('status', ['active', 'under_review', 'restricted'])->default('active');
            $table->timestamps();

            $table->foreign('creator_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('region_id')->references('id')->on('federation_regions')->nullOnDelete();

            $table->index(['region_id', 'stage']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('policy_proposals');
    }
};
