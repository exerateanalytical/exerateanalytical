<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fiscal_risk_signals', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('country_id');
            $table->integer('year');
            $table->enum('risk_type', ['debt_sustainability', 'budget_execution', 'revenue_instability']);
            $table->enum('severity', ['low', 'moderate', 'high', 'critical'])->default('moderate');
            $table->text('description')->nullable();
            $table->timestamp('triggered_at');
            $table->timestamps();
            $table->foreign('country_id')->references('id')->on('countries')->cascadeOnDelete();
            $table->index(['country_id', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fiscal_risk_signals');
    }
};
