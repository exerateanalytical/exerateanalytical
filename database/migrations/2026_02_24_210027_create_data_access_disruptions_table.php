<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('data_access_disruptions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('country_id');
            $table->integer('year');
            $table->enum('disruption_type', ['missing_budget_data', 'missing_debt_data', 'missing_service_data']);
            $table->text('description')->nullable();
            $table->timestamp('flagged_at');
            $table->enum('severity', ['low', 'moderate', 'high'])->default('moderate');
            $table->timestamps();
            $table->foreign('country_id')->references('id')->on('countries')->cascadeOnDelete();
            $table->index(['country_id', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_access_disruptions');
    }
};
