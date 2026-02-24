<?php

namespace App\Services\Transparency;

use App\Models\DataAccessDisruption;
use Illuminate\Support\Facades\Log;

class DataDisruptionMonitoringService
{
    public function flagMissingDataset(string $countryId, int $year, string $disruptionType, string $description = '', string $severity = 'moderate'): DataAccessDisruption
    {
        $disruption = DataAccessDisruption::create([
            'country_id' => $countryId,
            'year' => $year,
            'disruption_type' => $disruptionType,
            'description' => $description,
            'flagged_at' => now(),
            'severity' => $severity,
        ]);

        Log::warning('Data access disruption flagged', [
            'country_id' => $countryId,
            'year' => $year,
            'type' => $disruptionType,
            'severity' => $severity,
        ]);

        return $disruption;
    }

    public function getActiveDisruptions(string $countryId): \Illuminate\Database\Eloquent\Collection
    {
        return DataAccessDisruption::where('country_id', $countryId)
            ->orderBy('flagged_at', 'desc')
            ->get();
    }
}
