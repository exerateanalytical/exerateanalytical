<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->uuid('reactable_id');
            $table->string('reactable_type');
            $table->enum('type', ['support', 'oppose', 'insightful', 'concern', 'report']);
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();

            $table->index(['reactable_id', 'reactable_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reactions');
    }
};
