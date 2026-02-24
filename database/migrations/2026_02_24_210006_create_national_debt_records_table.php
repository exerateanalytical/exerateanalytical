<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('national_debt_records', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('country_id');
            $table->integer('year');
            $table->decimal('total_debt', 18, 2);
            $table->decimal('debt_to_gdp_ratio', 6, 2);
            $table->decimal('external_debt', 18, 2)->nullable();
            $table->decimal('domestic_debt', 18, 2)->nullable();
            $table->decimal('debt_service_total', 18, 2)->nullable();
            $table->decimal('debt_service_ratio', 6, 2)->nullable();
            $table->decimal('interest_payments', 18, 2)->nullable();
            $table->decimal('interest_as_budget_percent', 6, 2)->nullable();
            $table->enum('risk_classification', ['low', 'moderate', 'elevated', 'critical'])->default('moderate');
            $table->string('source_title');
            $table->text('source_url');
            $table->integer('data_version')->default(1);
            $table->uuid('created_by')->nullable();
            $table->timestamps();
            $table->foreign('country_id')->references('id')->on('countries')->cascadeOnDelete();
            $table->index(['country_id', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('national_debt_records');
    }
};
