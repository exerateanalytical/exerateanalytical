<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_access_records', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('country_id');
            $table->uuid('region_id')->nullable();
            $table->integer('year');
            $table->decimal('electricity_access_percent', 6, 2)->nullable();
            $table->decimal('safe_water_access_percent', 6, 2)->nullable();
            $table->decimal('road_density_km_per_100km2', 8, 2)->nullable();
            $table->decimal('paved_road_percent', 6, 2)->nullable();
            $table->integer('healthcare_facilities_total')->nullable();
            $table->decimal('healthcare_facilities_per_10000', 8, 2)->nullable();
            $table->integer('schools_total')->nullable();
            $table->decimal('schools_per_10000', 8, 2)->nullable();
            $table->decimal('internet_penetration_percent', 6, 2)->nullable();
            $table->decimal('mobile_network_coverage_percent', 6, 2)->nullable();
            $table->string('source_title');
            $table->text('source_url');
            $table->integer('data_version')->default(1);
            $table->uuid('created_by')->nullable();
            $table->timestamps();
            $table->foreign('country_id')->references('id')->on('countries')->cascadeOnDelete();
            $table->foreign('region_id')->references('id')->on('regions')->nullOnDelete();
            $table->index(['country_id', 'year']);
            $table->index(['region_id', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_access_records');
    }
};
