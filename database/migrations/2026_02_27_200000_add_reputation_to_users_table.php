<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->decimal('reputation_score', 8, 3)->default(0.000)->after('email');
            $table->string('reputation_tier', 32)->default('Citizen')->after('reputation_score');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['reputation_score', 'reputation_tier']);
        });
    }
};
