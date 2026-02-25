<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            CREATE UNIQUE INDEX fiscal_risk_signals_unique_country_year_risk_type
            ON fiscal_risk_signals (country_id, year, risk_type)
        ");

        DB::statement("
            CREATE UNIQUE INDEX national_debt_records_unique_country_year
            ON national_debt_records (country_id, year)
        ");

        DB::statement("
            CREATE UNIQUE INDEX revenue_records_unique_country_year
            ON revenue_records (country_id, year)
        ");

        DB::statement("
            CREATE UNIQUE INDEX budget_allocations_unique_country_year_sector
            ON budget_allocations (country_id, year, sector_name)
        ");
    }

    public function down(): void
    {
        DB::statement("DROP INDEX IF EXISTS fiscal_risk_signals_unique_country_year_risk_type");
        DB::statement("DROP INDEX IF EXISTS national_debt_records_unique_country_year");
        DB::statement("DROP INDEX IF EXISTS revenue_records_unique_country_year");
        DB::statement("DROP INDEX IF EXISTS budget_allocations_unique_country_year_sector");
    }
};
