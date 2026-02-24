<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('petition_signatures', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('petition_id');
            $table->string('ip_hash');
            $table->uuid('region_id')->nullable();
            $table->timestamp('signed_at');
            $table->timestamps();
            $table->foreign('petition_id')->references('id')->on('petitions')->cascadeOnDelete();
            $table->foreign('region_id')->references('id')->on('regions')->nullOnDelete();
            $table->unique(['petition_id', 'ip_hash']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('petition_signatures');
    }
};
