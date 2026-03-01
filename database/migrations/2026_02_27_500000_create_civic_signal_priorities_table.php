<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('civic_signal_priorities', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->enum('signal_type', ['poll', 'petition', 'policy']);
            $table->uuid('signal_id');
            $table->decimal('priority_score',         6, 3);
            $table->decimal('participation_velocity', 6, 3);
            $table->decimal('cross_region_factor',    6, 3);
            $table->decimal('trust_impact',           6, 3);
            $table->timestamp('calculated_at');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['signal_type', 'priority_score']);
            $table->index('calculated_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('civic_signal_priorities');
    }
};
