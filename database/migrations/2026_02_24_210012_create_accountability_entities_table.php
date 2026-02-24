<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accountability_entities', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('country_id');
            $table->uuid('institution_id');
            $table->uuid('region_id')->nullable();
            $table->integer('year');
            $table->string('mandate_area');
            $table->text('legal_basis_reference')->nullable();
            $table->timestamps();
            $table->foreign('country_id')->references('id')->on('countries')->cascadeOnDelete();
            $table->foreign('institution_id')->references('id')->on('institutions')->cascadeOnDelete();
            $table->foreign('region_id')->references('id')->on('regions')->nullOnDelete();
            $table->index(['country_id', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accountability_entities');
    }
};
