<?php

namespace App\Services\Development;

use App\Exceptions\DataIntegrityException;
use App\Models\Region;
use App\Models\ServiceAccessRecord;
use Illuminate\Support\Facades\Log;

class ServiceAccessService
{
    public function calculateHealthcareDensity(string $regionId, int $year): ?float
    {
        $record = ServiceAccessRecord::where('region_id', $regionId)
            ->where('year', $year)
            ->first();

        if (!$record) {
            Log::warning('No service access record found', ['region_id' => $regionId, 'year' => $year]);
            return null;
        }

        $region = Region::find($regionId);
        if (!$region || !$region->population || $region->population == 0) {
            throw new DataIntegrityException("Population data missing for region [{$regionId}].");
        }

        if ($record->healthcare_facilities_total === null || $record->healthcare_facilities_total < 0) {
            throw new DataIntegrityException("Invalid healthcare facilities count for region [{$regionId}].");
        }

        $density = $record->healthcare_facilities_total / ($region->population / 10000);
        $record->healthcare_facilities_per_10000 = round($density, 2);
        $record->save();

        return round($density, 2);
    }

    public function calculateSchoolDensity(string $regionId, int $year): ?float
    {
        $record = ServiceAccessRecord::where('region_id', $regionId)
            ->where('year', $year)
            ->first();

        if (!$record) return null;

        $region = Region::find($regionId);
        if (!$region || !$region->population || $region->population == 0) {
            throw new DataIntegrityException("Population data missing for region [{$regionId}].");
        }

        $density = ($record->schools_total ?? 0) / ($region->population / 10000);
        $record->schools_per_10000 = round($density, 2);
        $record->save();

        return round($density, 2);
    }

    public function validatePercentFields(array $data): bool
    {
        $percentFields = [
            'electricity_access_percent', 'safe_water_access_percent',
            'paved_road_percent', 'internet_penetration_percent',
            'mobile_network_coverage_percent',
        ];

        foreach ($percentFields as $field) {
            if (isset($data[$field])) {
                $val = (float) $data[$field];
                if ($val < 0 || $val > 100) {
                    throw new DataIntegrityException("Field [{$field}] must be between 0 and 100.");
                }
            }
        }

        return true;
    }
}
