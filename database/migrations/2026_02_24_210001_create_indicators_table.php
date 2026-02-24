<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('indicators', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('pillar_id');
            $table->uuid('country_id');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('unit')->nullable();
            $table->decimal('weight', 5, 2);
            $table->enum('normalization_method', ['minmax', 'zscore', 'inverse_minmax']);
            $table->enum('data_type', ['numeric', 'percentage', 'ratio', 'index'])->default('numeric');
            $table->enum('reliability_level', ['high', 'moderate', 'limited'])->default('moderate');
            $table->integer('reporting_lag_months')->default(0);
            $table->boolean('is_active')->default(true);
            $table->integer('version')->default(1);
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('pillar_id')->references('id')->on('pillars')->cascadeOnDelete();
            $table->foreign('country_id')->references('id')->on('countries')->cascadeOnDelete();
            $table->index(['pillar_id', 'version']);
            $table->index(['country_id', 'version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('indicators');
    }
};
