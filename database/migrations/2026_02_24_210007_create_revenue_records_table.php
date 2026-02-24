<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('revenue_records', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('country_id');
            $table->integer('year');
            $table->decimal('total_revenue', 18, 2);
            $table->decimal('tax_revenue', 18, 2)->nullable();
            $table->decimal('non_tax_revenue', 18, 2)->nullable();
            $table->decimal('grants', 18, 2)->nullable();
            $table->decimal('revenue_to_gdp_ratio', 6, 2)->nullable();
            $table->string('source_title');
            $table->text('source_url');
            $table->timestamps();
            $table->foreign('country_id')->references('id')->on('countries')->cascadeOnDelete();
            $table->index(['country_id', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('revenue_records');
    }
};
