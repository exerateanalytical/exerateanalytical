<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('regions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('country_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->integer('administrative_level')->default(1);
            $table->integer('population')->default(0);
            $table->decimal('area_km2', 12, 4)->default(0);
            $table->uuid('parent_region_id')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('country_id');
            $table->index('parent_region_id');
            $table->index(['country_id', 'administrative_level']);
        });

        Schema::table('regions', function (Blueprint $table) {
            $table->foreign('parent_region_id')->references('id')->on('regions')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('regions');
    }
};
