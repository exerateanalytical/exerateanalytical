<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('representation_records', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('country_id');
            $table->uuid('region_id')->nullable();
            $table->integer('year');
            $table->jsonb('gender_distribution')->nullable();
            $table->jsonb('regional_distribution')->nullable();
            $table->jsonb('age_distribution')->nullable();
            $table->jsonb('professional_background_distribution')->nullable();
            $table->decimal('equity_index_score', 6, 2)->nullable();
            $table->timestamps();
            $table->foreign('country_id')->references('id')->on('countries')->cascadeOnDelete();
            $table->foreign('region_id')->references('id')->on('regions')->nullOnDelete();
            $table->index(['country_id', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('representation_records');
    }
};
