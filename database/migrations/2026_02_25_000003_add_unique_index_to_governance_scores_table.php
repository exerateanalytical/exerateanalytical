<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Regional rows: region_id IS NOT NULL
        // Standard unique — NULL != NULL is not a concern here since region_id has a value
        DB::statement("
            CREATE UNIQUE INDEX governance_scores_unique_regional
            ON governance_scores (country_id, region_id, year, version)
            WHERE region_id IS NOT NULL
        ");

        // National rows: region_id IS NULL
        // Partial index excludes region_id from the key — PostgreSQL treats these correctly
        DB::statement("
            CREATE UNIQUE INDEX governance_scores_unique_national
            ON governance_scores (country_id, year, version)
            WHERE region_id IS NULL
        ");
    }

    public function down(): void
    {
        DB::statement("DROP INDEX IF EXISTS governance_scores_unique_regional");
        DB::statement("DROP INDEX IF EXISTS governance_scores_unique_national");
    }
};
