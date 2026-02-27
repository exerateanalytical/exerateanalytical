<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('governance_actions', function (Blueprint $table) {
            // Lifecycle status — default 'executed' preserves all existing rows
            $table->enum('status', ['proposed', 'approved', 'rejected', 'executed', 'archived'])
                ->default('executed')
                ->after('executed_at');

            // Who proposed (nullable — direct executions leave this null)
            $table->uuid('proposed_by')->nullable()->after('status');
            $table->foreign('proposed_by')->references('id')->on('users')->cascadeOnDelete();

            // Who approved (nullable until approval)
            $table->uuid('approved_by')->nullable()->after('proposed_by');
            $table->foreign('approved_by')->references('id')->on('users')->nullOnDelete();

            // Approval timestamp
            $table->timestamp('approved_at')->nullable()->after('approved_by');

            // Human-readable notes (rejection reason, approval comments, etc.)
            $table->text('notes')->nullable()->after('approved_at');

            // Fast lookup of pending approvals and status-ordered audits
            $table->index(['status', 'executed_at'], 'governance_actions_status_executed_at_idx');
        });
    }

    public function down(): void
    {
        Schema::table('governance_actions', function (Blueprint $table) {
            $table->dropIndex('governance_actions_status_executed_at_idx');
            $table->dropForeign(['proposed_by']);
            $table->dropForeign(['approved_by']);
            $table->dropColumn(['status', 'proposed_by', 'approved_by', 'approved_at', 'notes']);
        });
    }
};
