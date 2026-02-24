<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('indicator_reliability_scores', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('indicator_id');
            $table->enum('reliability_level', ['high', 'moderate', 'limited'])->default('moderate');
            $table->text('confidence_notes')->nullable();
            $table->integer('reporting_lag_months')->default(0);
            $table->enum('verification_status', ['single_source', 'multi_source_confirmed'])->default('single_source');
            $table->timestamp('last_reviewed_at')->nullable();
            $table->timestamps();
            $table->foreign('indicator_id')->references('id')->on('indicators')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('indicator_reliability_scores');
    }
};
