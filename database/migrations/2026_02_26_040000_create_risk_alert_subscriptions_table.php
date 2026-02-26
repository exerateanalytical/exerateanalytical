<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('risk_alert_subscriptions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('country_id')->nullable();
            $table->string('alert_type')->nullable();
            $table->string('severity')->nullable();
            $table->string('channel');
            $table->json('channel_config');
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index(['country_id', 'alert_type', 'severity', 'active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('risk_alert_subscriptions');
    }
};
