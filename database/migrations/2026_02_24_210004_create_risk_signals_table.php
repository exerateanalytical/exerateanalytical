<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('risk_signals', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('country_id');
            $table->string('signal_type');
            $table->string('severity')->default('moderate');
            $table->text('description')->nullable();
            $table->string('module')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->boolean('is_escalated')->default(false);
            $table->timestamp('triggered_at');
            $table->timestamps();
            $table->foreign('country_id')->references('id')->on('countries')->cascadeOnDelete();
            $table->index(['country_id', 'signal_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('risk_signals');
    }
};
