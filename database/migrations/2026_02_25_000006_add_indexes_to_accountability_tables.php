<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            CREATE INDEX IF NOT EXISTS accountability_audit_log_entity_id_idx
            ON accountability_audit_log (entity_id)
        ");

        DB::statement("
            CREATE INDEX IF NOT EXISTS accountability_entities_institution_id_idx
            ON accountability_entities (institution_id)
        ");
    }

    public function down(): void
    {
        DB::statement("DROP INDEX IF EXISTS accountability_audit_log_entity_id_idx");
        DB::statement("DROP INDEX IF EXISTS accountability_entities_institution_id_idx");
    }
};
