<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accountability_audit_log', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('entity_id');
            $table->jsonb('previous_score')->nullable();
            $table->jsonb('new_score')->nullable();
            $table->uuid('changed_by')->nullable();
            $table->timestamp('changed_at');
            $table->timestamps();
            $table->foreign('entity_id')->references('id')->on('accountability_entities')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accountability_audit_log');
    }
};
