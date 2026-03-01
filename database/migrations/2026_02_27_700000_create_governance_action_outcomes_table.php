<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('governance_action_outcomes', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('governance_action_id');
            $table->foreign('governance_action_id')
                ->references('id')
                ->on('governance_actions')
                ->cascadeOnDelete();

            $table->enum('observation_window', ['24h', '7d', '30d']);

            $table->decimal('stability_delta',     5, 2);
            $table->decimal('trust_delta',         5, 2);
            $table->decimal('participation_delta', 5, 2);
            $table->decimal('cascade_delta',       5, 3);

            $table->decimal('effectiveness_score', 6, 2);

            $table->timestamp('measured_at');
            $table->timestamp('created_at')->useCurrent();

            $table->index(
                ['governance_action_id', 'observation_window'],
                'gov_outcomes_action_window_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('governance_action_outcomes');
    }
};
