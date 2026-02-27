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
            $table->uuid('user_id');
            $table->decimal('weight', 6, 3)->default(1.000);
            $table->timestamp('signed_at')->useCurrent();
            $table->timestamps();

            $table->foreign('petition_id')->references('id')->on('petitions')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();

            $table->unique(['petition_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('petition_signatures');
    }
};
