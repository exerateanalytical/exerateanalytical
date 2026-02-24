<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accountability_links', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('accountability_entity_id');
            $table->uuid('linked_budget_id')->nullable();
            $table->uuid('linked_project_id')->nullable();
            $table->uuid('linked_indicator_id')->nullable();
            $table->uuid('linked_policy_id')->nullable();
            $table->timestamps();
            $table->foreign('accountability_entity_id')->references('id')->on('accountability_entities')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accountability_links');
    }
};
