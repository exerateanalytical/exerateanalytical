<?php

namespace App\Services\Transparency;

use App\Exceptions\IndicatorWeightMismatchException;
use App\Models\MethodologyVersion;
use App\Models\Pillar;
use Illuminate\Support\Facades\Log;

class MethodologyGovernanceService
{
    private const WEIGHT_TOLERANCE = 0.01;

    public function publishNewVersion(string $countryId, array $changeData): MethodologyVersion
    {
        $this->validateWeights($countryId);

        MethodologyVersion::where('country_id', $countryId)
            ->where('is_active', true)
            ->update(['is_active' => false]);

        $latestVersion = MethodologyVersion::where('country_id', $countryId)
            ->max('version_number') ?? 0;

        $version = MethodologyVersion::create([
            'country_id' => $countryId,
            'version_number' => $latestVersion + 1,
            'description_of_change' => $changeData['description'] ?? null,
            'change_rationale' => $changeData['rationale'] ?? null,
            'impact_summary' => $changeData['impact'] ?? null,
            'is_active' => true,
            'published_at' => now(),
            'created_by' => auth()->id(),
        ]);

        Log::info('New methodology version published', [
            'country_id' => $countryId,
            'version' => $version->version_number,
        ]);

        return $version;
    }

    private function validateWeights(string $countryId): void
    {
        $pillars = Pillar::where('country_id', $countryId)->active()->get();
        $weightSum = $pillars->sum(fn ($p) => (float) $p->weight);

        if ($pillars->isNotEmpty() && abs($weightSum - 100.0) > self::WEIGHT_TOLERANCE) {
            throw new IndicatorWeightMismatchException(
                "Pillar weights for country [{$countryId}] sum to {$weightSum}, not 100."
            );
        }
    }

    public function getActiveVersion(string $countryId): ?MethodologyVersion
    {
        return MethodologyVersion::where('country_id', $countryId)
            ->where('is_active', true)
            ->latest('published_at')
            ->first();
    }
}
