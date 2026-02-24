<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('right_of_reply_submissions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('country_id');
            $table->uuid('institution_id')->nullable();
            $table->string('related_module')->nullable();
            $table->uuid('related_record_id')->nullable();
            $table->text('response_text');
            $table->enum('status', ['pending', 'approved', 'rejected', 'published'])->default('pending');
            $table->string('submitted_by');
            $table->timestamp('submitted_at');
            $table->uuid('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            $table->foreign('country_id')->references('id')->on('countries')->cascadeOnDelete();
            $table->foreign('institution_id')->references('id')->on('institutions')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('right_of_reply_submissions');
    }
};
