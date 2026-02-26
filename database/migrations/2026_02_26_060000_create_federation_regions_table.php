<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('federation_regions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('code', 20)->unique(); // e.g. 'emea', 'apac', 'amer'
            $table->timestamps();

            $table->index('code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('federation_regions');
    }
};
