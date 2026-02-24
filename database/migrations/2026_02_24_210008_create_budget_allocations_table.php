<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budget_allocations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('country_id');
            $table->uuid('region_id')->nullable();
            $table->integer('year');
            $table->string('sector_name');
            $table->decimal('allocated_amount', 18, 2);
            $table->decimal('executed_amount', 18, 2)->default(0);
            $table->decimal('execution_rate', 6, 2)->default(0);
            $table->boolean('delay_flag')->default(false);
            $table->string('source_title');
            $table->text('source_url');
            $table->timestamps();
            $table->foreign('country_id')->references('id')->on('countries')->cascadeOnDelete();
            $table->foreign('region_id')->references('id')->on('regions')->nullOnDelete();
            $table->index(['country_id', 'year']);
            $table->index(['region_id', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budget_allocations');
    }
};
