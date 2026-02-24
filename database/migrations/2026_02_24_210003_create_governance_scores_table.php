<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('governance_scores', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('country_id');
            $table->uuid('region_id')->nullable();
            $table->integer('year');
            $table->jsonb('pillar_scores')->nullable();
            $table->decimal('composite_score', 5, 2);
            $table->integer('version')->default(1);
            $table->timestamp('calculated_at')->nullable();
            $table->uuid('calculated_by')->nullable();
            $table->timestamps();
            $table->foreign('country_id')->references('id')->on('countries')->cascadeOnDelete();
            $table->foreign('region_id')->references('id')->on('regions')->nullOnDelete();
            $table->index(['country_id', 'year']);
            $table->index(['region_id', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('governance_scores');
    }
};
