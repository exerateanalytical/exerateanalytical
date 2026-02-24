<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pillars', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('country_id')->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('weight', 5, 2);
            $table->boolean('is_active')->default(true);
            $table->integer('version')->default(1);
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('country_id')->references('id')->on('countries')->nullOnDelete();
            $table->index(['country_id', 'version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pillars');
    }
};
