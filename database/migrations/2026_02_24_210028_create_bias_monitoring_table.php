<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bias_monitoring', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('country_id');
            $table->string('administration_period')->nullable();
            $table->decimal('average_governance_score', 8, 4)->nullable();
            $table->decimal('score_variance', 8, 4)->nullable();
            $table->integer('methodology_version')->nullable();
            $table->timestamp('analysis_date')->nullable();
            $table->timestamps();
            $table->foreign('country_id')->references('id')->on('countries')->cascadeOnDelete();
            $table->index('country_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bias_monitoring');
    }
};
