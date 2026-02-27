<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('governance_scenarios', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('recommendation_id');
            $table->foreign('recommendation_id')
                ->references('id')
                ->on('governance_recommendations')
                ->cascadeOnDelete();

            $table->string('scenario_type', 40); // accept_action | ignore_action | delayed_action

            $table->json('projection_payload');   // computation metadata + baseline values
            $table->decimal('projected_stability',      5, 2);  // absolute value 0–100
            $table->decimal('projected_cascade_index',  5, 3);  // absolute value 0–1
            $table->decimal('projected_trust_delta',    5, 2);  // signed delta
            $table->decimal('confidence',               5, 2);  // 0–100

            $table->timestamp('created_at')->useCurrent();

            $table->index('recommendation_id', 'gov_scenarios_rec_id_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('governance_scenarios');
    }
};
