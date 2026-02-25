<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            CREATE UNIQUE INDEX accountability_scores_unique_entity
            ON accountability_scores (accountability_entity_id)
        ");
    }

    public function down(): void
    {
        DB::statement("DROP INDEX IF EXISTS accountability_scores_unique_entity");
    }
};
