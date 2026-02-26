<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('risk_alert_events', function (Blueprint $table) {
            $table->index(
                ['country_id', 'event_type', 'created_at'],
                'rae_country_event_type_created_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::table('risk_alert_events', function (Blueprint $table) {
            $table->dropIndex('rae_country_event_type_created_idx');
        });
    }
};
