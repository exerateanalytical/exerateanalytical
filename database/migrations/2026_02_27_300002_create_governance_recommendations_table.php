<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('governance_recommendations', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('recommendation_type', 40);
            $table->enum('severity', ['low', 'medium', 'high', 'critical']);
            $table->string('title');
            $table->text('rationale');
            $table->json('suggested_action');
            $table->decimal('confidence_score', 5, 2);
            $table->timestamp('source_snapshot_at')->nullable();

            $table->enum('status', ['active', 'accepted', 'dismissed', 'expired'])->default('active');
            $table->uuid('accepted_action_id')->nullable();
            $table->foreign('accepted_action_id')
                ->references('id')
                ->on('governance_actions')
                ->nullOnDelete();

            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('expires_at')->nullable();

            $table->index(['status', 'severity'], 'gov_rec_status_severity_idx');
            $table->index('created_at', 'gov_rec_created_at_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('governance_recommendations');
    }
};
