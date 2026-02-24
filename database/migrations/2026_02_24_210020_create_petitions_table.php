<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('petitions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('country_id');
            $table->string('title');
            $table->text('description');
            $table->string('category')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected', 'closed'])->default('pending');
            $table->string('content_hash')->nullable();
            $table->uuid('created_by')->nullable();
            $table->timestamps();
            $table->foreign('country_id')->references('id')->on('countries')->cascadeOnDelete();
            $table->index(['country_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('petitions');
    }
};
