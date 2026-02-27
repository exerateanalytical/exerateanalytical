<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * UPDATED_AT is intentionally omitted — GovernanceAction is immutable once
     * persisted; executed_at carries the authoritative timestamp.
     */
    public function up(): void
    {
        Schema::create('governance_actions', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('actor_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->enum('action_type', [
                'simulate_contagion',
                'simulate_shock',
                'launch_poll',
                'open_consultation',
                'flag_region',
            ]);

            $table->uuid('target_id')->nullable();
            $table->string('target_type')->nullable();

            $table->json('parameters')->nullable();
            $table->json('result_snapshot')->nullable();

            $table->timestamp('executed_at');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['action_type', 'executed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('governance_actions');
    }
};
