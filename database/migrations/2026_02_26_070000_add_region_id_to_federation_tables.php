<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // countries: FK-constrained since countries has a proper UUID PK
        Schema::table('countries', function (Blueprint $table) {
            $table->foreignUuid('region_id')
                ->nullable()
                ->after('continent_region')
                ->constrained('federation_regions')
                ->nullOnDelete();

            $table->index('region_id');
        });

        // risk_alerts: plain nullable uuid, consistent with string country_id pattern
        Schema::table('risk_alerts', function (Blueprint $table) {
            $table->uuid('region_id')->nullable()->after('country_id');
            $table->index('region_id');
        });

        // risk_alert_events: plain nullable uuid
        Schema::table('risk_alert_events', function (Blueprint $table) {
            $table->uuid('region_id')->nullable()->after('country_id');
            $table->index('region_id');
        });

        // risk_alert_subscriptions: plain nullable uuid
        Schema::table('risk_alert_subscriptions', function (Blueprint $table) {
            $table->uuid('region_id')->nullable()->after('country_id');
            $table->index('region_id');
        });

        // exposure_matrices: plain nullable uuid
        Schema::table('exposure_matrices', function (Blueprint $table) {
            $table->uuid('region_id')->nullable()->after('active');
            $table->index('region_id');
        });
    }

    public function down(): void
    {
        Schema::table('countries', function (Blueprint $table) {
            $table->dropForeign(['region_id']);
            $table->dropIndex(['region_id']);
            $table->dropColumn('region_id');
        });

        foreach (['risk_alerts', 'risk_alert_events', 'risk_alert_subscriptions', 'exposure_matrices'] as $t) {
            Schema::table($t, function (Blueprint $table) {
                $table->dropIndex(['region_id']);
                $table->dropColumn('region_id');
            });
        }
    }
};
