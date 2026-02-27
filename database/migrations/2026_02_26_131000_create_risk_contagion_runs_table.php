<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('risk_contagion_runs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->timestamp('executed_at');
            $table->json('baseline_vector');
            $table->json('final_vector');
            $table->decimal('cascade_index', 8, 4);
            $table->unsignedTinyInteger('iteration_count');
            $table->json('parameters');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('risk_contagion_runs');
    }
};
