<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('representation_metrics', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('region_id');
            $table->unsignedInteger('eligible_population');
            $table->unsignedInteger('active_users');
            $table->unsignedInteger('verified_users');
            $table->decimal('participation_rate', 6, 3);
            $table->decimal('representation_gap', 6, 3);
            $table->timestamp('calculated_at');
            $table->timestamps();

            $table->foreign('region_id')->references('id')->on('federation_regions')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('representation_metrics');
    }
};
