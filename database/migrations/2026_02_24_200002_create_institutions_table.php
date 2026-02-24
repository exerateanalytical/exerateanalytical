<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('institutions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('country_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->enum('type', ['ministry', 'agency', 'department', 'parliament', 'judiciary']);
            $table->text('legal_mandate')->nullable();
            $table->decimal('transparency_score', 5, 2)->default(0.00);
            $table->timestamps();
            $table->softDeletes();

            $table->index('country_id');
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('institutions');
    }
};
