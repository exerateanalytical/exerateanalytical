<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('survey_questions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('survey_id');
            $table->text('question_text');
            $table->enum('question_type', ['likert', 'binary', 'multiple_choice'])->default('likert');
            $table->integer('scale_min')->nullable();
            $table->integer('scale_max')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->foreign('survey_id')->references('id')->on('surveys')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('survey_questions');
    }
};
