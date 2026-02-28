<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('governance_trajectories', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->enum('scope', ['global', 'region']);

            $table->foreignUuid('region_id')
                ->nullable()
                ->constrained('federation_regions')
                ->nullOnDelete();

            $table->decimal('stability_delta',     6, 3)->default(0);
            $table->decimal('trust_delta',         6, 3)->default(0);
            $table->decimal('participation_delta', 6, 3)->default(0);
            $table->decimal('contagion_delta',     6, 3)->default(0);
            $table->decimal('trajectory_score',    6, 3)->default(0);

            $table->enum('direction', ['improving', 'stable', 'declining'])
                ->default('stable');

            // Immutable time-series rows: calculated_at is the computation timestamp,
            // created_at is the DB insert timestamp.
            $table->timestamp('calculated_at');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['scope', 'calculated_at']);
            $table->index(['region_id', 'calculated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('governance_trajectories');
    }
};
