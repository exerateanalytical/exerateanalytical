<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('regional_systemic_snapshots', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('region_id')->constrained('federation_regions')->cascadeOnDelete();
            $table->string('region_code', 20);
            $table->timestamp('snapshot_at');
            $table->json('payload');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['region_id', 'snapshot_at']);
            $table->index('snapshot_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('regional_systemic_snapshots');
    }
};
