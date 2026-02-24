<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('methodology_versions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('country_id')->nullable();
            $table->integer('version_number');
            $table->text('description_of_change')->nullable();
            $table->text('change_rationale')->nullable();
            $table->text('impact_summary')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('published_at')->nullable();
            $table->uuid('created_by')->nullable();
            $table->timestamps();
            $table->foreign('country_id')->references('id')->on('countries')->nullOnDelete();
            $table->index(['country_id', 'version_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('methodology_versions');
    }
};
