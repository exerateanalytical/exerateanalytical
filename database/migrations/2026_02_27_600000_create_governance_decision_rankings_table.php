<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('governance_decision_rankings', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('recommendation_id');
            $table->foreign('recommendation_id')
                ->references('id')
                ->on('governance_recommendations')
                ->cascadeOnDelete();

            $table->string('scenario_type', 40);

            $table->decimal('projected_stability_delta',    5, 2);
            $table->decimal('projected_trust_delta',        5, 2);
            $table->decimal('projected_cascade_delta',      5, 3);
            $table->decimal('projected_participation_delta',5, 2);

            $table->decimal('decision_score', 6, 2);
            $table->integer('rank_position');

            $table->timestamp('created_at')->useCurrent();

            $table->index(['recommendation_id', 'rank_position'], 'gov_dec_rankings_rec_rank_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('governance_decision_rankings');
    }
};
