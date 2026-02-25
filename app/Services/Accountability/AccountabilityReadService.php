<?php

namespace App\Services\Accountability;

use App\Models\AccountabilityEntity;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class AccountabilityReadService
{
    public function getCountryScores(string $countryId, int $year): Collection
    {
        return Cache::remember("accountability:{$countryId}:{$year}", 3600, function () use ($countryId, $year) {
            return AccountabilityEntity::with(['institution', 'score'])
                ->where('country_id', $countryId)
                ->where('year', $year)
                ->get();
        });
    }

    public function getRegionalScores(string $countryId, string $regionId, int $year): Collection
    {
        return AccountabilityEntity::with(['institution', 'score'])
            ->where('country_id', $countryId)
            ->where('region_id', $regionId)
            ->where('year', $year)
            ->get();
    }

    public function getInstitutionScores(string $countryId, string $institutionId): Collection
    {
        return AccountabilityEntity::with(['score', 'links'])
            ->where('country_id', $countryId)
            ->where('institution_id', $institutionId)
            ->orderBy('year', 'desc')
            ->get();
    }
}
