<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('survey_responses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('survey_id');
            $table->uuid('region_id')->nullable();
            $table->string('age_group')->nullable();
            $table->string('gender')->nullable();
            $table->jsonb('response_data');
            $table->string('ip_hash');
            $table->timestamp('submitted_at');
            $table->timestamps();
            $table->foreign('survey_id')->references('id')->on('surveys')->cascadeOnDelete();
            $table->foreign('region_id')->references('id')->on('regions')->nullOnDelete();
            $table->index('survey_id');
            $table->index('region_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('survey_responses');
    }
};
