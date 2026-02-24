<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('indicator_values', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('indicator_id');
            $table->uuid('country_id');
            $table->uuid('region_id')->nullable();
            $table->integer('year');
            $table->decimal('raw_value', 20, 6);
            $table->decimal('normalized_value', 10, 6)->nullable();
            $table->string('source_title');
            $table->text('source_url');
            $table->string('source_document')->nullable();
            $table->integer('data_version')->default(1);
            $table->uuid('created_by')->nullable();
            $table->timestamps();
            $table->foreign('indicator_id')->references('id')->on('indicators')->cascadeOnDelete();
            $table->foreign('country_id')->references('id')->on('countries')->cascadeOnDelete();
            $table->foreign('region_id')->references('id')->on('regions')->nullOnDelete();
            $table->index(['country_id', 'year']);
            $table->index(['region_id', 'year']);
            $table->unique(['indicator_id', 'country_id', 'region_id', 'year', 'data_version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('indicator_values');
    }
};
