<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approval_ratings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('country_id');
            $table->uuid('region_id')->nullable();
            $table->integer('year');
            $table->decimal('approve_percent', 6, 2)->default(0);
            $table->decimal('disapprove_percent', 6, 2)->default(0);
            $table->decimal('neutral_percent', 6, 2)->default(0);
            $table->integer('sample_size')->default(0);
            $table->decimal('margin_of_error', 6, 2)->nullable();
            $table->string('confidence_interval')->nullable();
            $table->decimal('rolling_average_90_day', 6, 2)->nullable();
            $table->timestamp('calculated_at')->nullable();
            $table->timestamps();
            $table->foreign('country_id')->references('id')->on('countries')->cascadeOnDelete();
            $table->foreign('region_id')->references('id')->on('regions')->nullOnDelete();
            $table->index(['country_id', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_ratings');
    }
};
