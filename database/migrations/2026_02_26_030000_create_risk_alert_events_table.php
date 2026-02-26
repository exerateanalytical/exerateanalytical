<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('risk_alert_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('risk_alert_id')->index();
            $table->string('country_id');
            $table->string('type');
            $table->string('event_type');
            $table->string('severity');
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('risk_alert_events');
    }
};
