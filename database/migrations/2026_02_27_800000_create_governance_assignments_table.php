<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('governance_assignments', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('governance_action_id');
            $table->foreign('governance_action_id')
                ->references('id')
                ->on('governance_actions')
                ->cascadeOnDelete();

            $table->string('institution_name', 120);

            $table->uuid('assigned_by');
            $table->foreign('assigned_by')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();

            $table->enum('status', [
                'assigned',
                'acknowledged',
                'in_progress',
                'completed',
                'blocked',
            ])->default('assigned');

            $table->integer('progress_percent')->default(0);

            $table->text('notes')->nullable();

            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->timestamp('created_at')->useCurrent();

            $table->index('governance_action_id',      'gov_assignments_action_idx');
            $table->index(['status', 'created_at'],    'gov_assignments_status_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('governance_assignments');
    }
};
