<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accountability_scores', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('accountability_entity_id');
            $table->decimal('budget_execution_score', 6, 2)->default(0);
            $table->decimal('delivery_score', 6, 2)->default(0);
            $table->decimal('service_impact_score', 6, 2)->default(0);
            $table->decimal('transparency_score', 6, 2)->default(0);
            $table->decimal('composite_accountability_score', 6, 2)->default(0);
            $table->timestamp('calculated_at')->nullable();
            $table->timestamps();
            $table->foreign('accountability_entity_id')->references('id')->on('accountability_entities')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accountability_scores');
    }
};
