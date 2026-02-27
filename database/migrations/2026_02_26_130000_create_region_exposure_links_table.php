<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('region_exposure_links', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('source_region_id')
                  ->constrained('federation_regions')
                  ->cascadeOnDelete();
            $table->foreignUuid('target_region_id')
                  ->constrained('federation_regions')
                  ->cascadeOnDelete();
            $table->decimal('exposure_weight', 5, 4);
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['source_region_id', 'target_region_id']);
            $table->index('source_region_id');
            $table->index('target_region_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('region_exposure_links');
    }
};
