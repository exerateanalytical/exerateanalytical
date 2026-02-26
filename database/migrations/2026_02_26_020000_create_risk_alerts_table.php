<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('risk_alerts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('country_id');
            $table->string('type');
            $table->string('severity');
            $table->boolean('active')->default(true);
            $table->timestamp('acknowledged_at')->nullable();
            $table->string('acknowledged_by')->nullable();
            $table->timestamp('first_triggered_at');
            $table->timestamp('last_triggered_at');
            $table->timestamps();

            $table->index(['country_id', 'active']);
            $table->index(['country_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('risk_alerts');
    }
};
