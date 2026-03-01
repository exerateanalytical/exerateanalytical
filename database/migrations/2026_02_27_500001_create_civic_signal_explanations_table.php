<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('civic_signal_explanations', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('signal_priority_id')
                ->constrained('civic_signal_priorities')
                ->cascadeOnDelete();

            // Dominant causal factor among the four drivers
            $table->string('primary_driver', 64);

            // Raw driver values for bar-chart rendering
            // Keys: participation_velocity, trust_delta, contagion_pressure, representation_gap
            $table->json('driver_breakdown');

            // Regions with notable governance influence in the latest batch
            // e.g. [{"name": "Northern Alliance", "code": "NA"}, …]
            $table->json('affected_regions');

            $table->enum('trajectory_direction', ['improving', 'stable', 'deteriorating']);
            $table->enum('projected_risk_level', ['low', 'medium', 'high', 'critical']);

            $table->text('explanation_summary');

            $table->timestamp('created_at')->useCurrent();

            $table->index('signal_priority_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('civic_signal_explanations');
    }
};
