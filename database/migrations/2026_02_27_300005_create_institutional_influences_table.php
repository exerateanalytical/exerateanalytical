<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('institutional_influences', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('actor_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignUuid('region_id')
                ->nullable()
                ->constrained('federation_regions')
                ->nullOnDelete();

            // civic_leader | policy_driver | trust_stabilizer | volatility_source
            $table->string('influence_category', 40);

            $table->decimal('influence_score', 6, 3);
            $table->integer('activity_volume')->default(0);
            $table->decimal('trust_delta', 6, 3)->default(0);

            // Structured breakdown for the UI drawer
            $table->json('activity_breakdown')->nullable();

            $table->timestamp('calculated_at')->useCurrent();

            $table->index(['actor_id', 'calculated_at']);
            $table->index(['region_id', 'influence_score']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('institutional_influences');
    }
};
