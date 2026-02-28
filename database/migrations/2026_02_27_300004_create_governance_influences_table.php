<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('governance_influences', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('region_id')->nullable();
            $table->foreign('region_id')
                ->references('id')
                ->on('federation_regions')
                ->nullOnDelete();

            // What kind of system source drove this signal
            $table->string('source_type', 40);   // poll | petition | policy | trust | risk

            // UUID of the specific source record (nullable for aggregate signals)
            $table->uuid('source_id')->nullable();

            // The computed influence category
            $table->string('influence_type', 40); // trust_shift | participation_gap | contagion_pressure

            $table->decimal('influence_score', 6, 3);  // 0–100 normalised scale
            $table->enum('impact_direction', ['up', 'down']); // up = risk rising, down = risk falling

            $table->timestamp('calculated_at')->useCurrent();

            $table->index(['region_id', 'calculated_at'], 'gov_inf_region_calc_idx');
            $table->index('influence_type',              'gov_inf_type_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('governance_influences');
    }
};
