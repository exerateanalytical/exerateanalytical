<?php

namespace App\Services\Dashboard;

use App\Models\Country;
use App\Services\Risk\RiskIntelligenceService;

class ExecutiveRiskDashboardService
{
    public function __construct(private readonly RiskIntelligenceService $riskService) {}

    public function getRiskRanking(): array
    {
        return Country::active()->get()
            ->map(fn ($country) => array_merge(
                [
                    'country_id' => $country->id,
                    'name'       => $country->name,
                    'iso_code'   => $country->iso_code,
                ],
                $this->riskService->getNationalRiskSummary($country->id)
            ))
            ->sortByDesc('national_risk_score')
            ->values()
            ->all();
    }

    public function getRiskDrivers(string $countryId): array
    {
        return array_merge(
            ['country_id' => $countryId],
            $this->riskService->getDomainAverages($countryId)
        );
    }

    public function getRiskHistory(string $countryId): array
    {
        return $this->riskService->getMonthlyHistory($countryId);
    }

    public function getAlertWatchlist(): array
    {
        return Country::active()->get()
            ->map(fn ($country) => array_merge(
                [
                    'country_id' => $country->id,
                    'name'       => $country->name,
                    'iso_code'   => $country->iso_code,
                ],
                $this->riskService->getNationalRiskSummary($country->id)
            ))
            ->filter(fn ($entry) =>
                $entry['risk_level'] === 'critical'
                || ($entry['trend'] === 'deteriorating' && $entry['national_risk_score'] > 60)
            )
            ->values()
            ->all();
    }
}
