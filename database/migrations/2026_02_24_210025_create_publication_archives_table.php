<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('publication_archives', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('country_id');
            $table->integer('year');
            $table->enum('report_type', ['annual_governance', 'fiscal', 'development', 'continental'])->default('annual_governance');
            $table->string('file_path');
            $table->string('file_hash')->nullable();
            $table->boolean('is_locked')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->foreign('country_id')->references('id')->on('countries')->cascadeOnDelete();
            $table->unique(['country_id', 'year', 'report_type']);
            $table->index(['country_id', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('publication_archives');
    }
};
