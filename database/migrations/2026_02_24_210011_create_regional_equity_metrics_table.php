<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('regional_equity_metrics', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('country_id');
            $table->integer('year');
            $table->decimal('electricity_variance', 8, 4)->nullable();
            $table->decimal('water_variance', 8, 4)->nullable();
            $table->decimal('road_density_variance', 8, 4)->nullable();
            $table->decimal('healthcare_density_variance', 8, 4)->nullable();
            $table->decimal('education_density_variance', 8, 4)->nullable();
            $table->decimal('digital_access_variance', 8, 4)->nullable();
            $table->decimal('equity_score', 6, 2)->nullable();
            $table->timestamp('calculated_at')->nullable();
            $table->timestamps();
            $table->foreign('country_id')->references('id')->on('countries')->cascadeOnDelete();
            $table->unique(['country_id', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('regional_equity_metrics');
    }
};
