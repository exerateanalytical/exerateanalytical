<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('federated_global_snapshots', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->timestamp('snapshot_at');
            $table->unsignedInteger('region_count');
            $table->json('payload');
            $table->timestamp('created_at')->useCurrent();

            $table->index('snapshot_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('federated_global_snapshots');
    }
};
