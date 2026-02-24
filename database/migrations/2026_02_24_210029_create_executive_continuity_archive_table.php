<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('executive_continuity_archive', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('document_type');
            $table->string('file_path');
            $table->string('file_hash')->nullable();
            $table->timestamp('uploaded_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('executive_continuity_archive');
    }
};
