<?php

namespace App\Services\Risk;

use App\Models\FederationRegion;
use App\Models\RegionExposureLink;
use App\Models\RegionalSystemicSnapshot;
use App\Models\RiskContagionRun;
use Illuminate\Support\Carbon;

class RiskContagionService
{
    private const MAX_ITERATIONS = 8;
    private const EPSILON = 0.0001;
    private const MAX_CONTAGION_FACTOR = 0.7;

    public function execute(
        string $regionId,
        float $shockMagnitude,
        float $contagionFactor = 0.5,
        int $iterations = 5,
    ): RiskContagionRun {
        $contagionFactor = min(self::MAX_CONTAGION_FACTOR, max(0.0, $contagionFactor));
        $iterations      = min(self::MAX_ITERATIONS, max(1, $iterations));

        $regions = FederationRegion::all();
        $current = [];
        $delta   = [];

        foreach ($regions as $region) {
            $snapshot = RegionalSystemicSnapshot::where('region_id', $region->id)
                ->latest('snapshot_at')
                ->first();

            $risk = (float) ($snapshot->payload['mean_risk_score']      ?? 0.0);
            $frag = (float) ($snapshot->payload['mean_fragility_index'] ?? 0.0);
            $vol  = (float) ($snapshot->payload['mean_volatility_index'] ?? 0.0);

            $current[$region->id] = [
                'risk'       => $risk,
                'fragility'  => $frag,
                'volatility' => $vol,
            ];

            $delta[$region->id] = 0.0;
        }

        $current[$regionId]['risk'] += $shockMagnitude;
        $delta[$regionId]            = $shockMagnitude;

        $baseline = $current;
        $weights  = $this->buildWeightMatrix();

        $iterationCount = 0;

        for ($i = 0; $i < $iterations; $i++) {
            $iterationCount++;
            $maxDelta = 0.0;
            $next     = $current;

            foreach ($regions as $target) {
                $incoming = 0.0;

                foreach ($regions as $source) {
                    if (!isset($weights[$source->id][$target->id])) {
                        continue;
                    }

                    $incoming += $weights[$source->id][$target->id]
                        * $delta[$source->id]
                        * $contagionFactor;
                }

                if ($incoming == 0.0) {
                    continue;
                }

                $next[$target->id]['risk']       += $incoming;
                $next[$target->id]['fragility']  += $incoming * 0.8;
                $next[$target->id]['volatility'] += $incoming * 1.2;

                $next[$target->id]['risk']       = $this->clampBounded($next[$target->id]['risk']);
                $next[$target->id]['fragility']  = $this->clampBounded($next[$target->id]['fragility']);
                $next[$target->id]['volatility'] = max(0.0, $next[$target->id]['volatility']);

                $maxDelta = max($maxDelta, abs($incoming));
            }

            if ($maxDelta < self::EPSILON) {
                break;
            }

            foreach ($regions as $region) {
                $delta[$region->id] = $next[$region->id]['risk'] - $current[$region->id]['risk'];
            }

            $current = $next;
        }

        $cascadeIndex = $this->computeCascadeIndex($baseline, $current);

        return RiskContagionRun::create([
            'executed_at'    => Carbon::now(),
            'baseline_vector' => $baseline,
            'final_vector'   => $current,
            'cascade_index'  => $cascadeIndex,
            'iteration_count' => $iterationCount,
            'parameters'     => [
                'region_id'        => $regionId,
                'shock_magnitude'  => $shockMagnitude,
                'contagion_factor' => $contagionFactor,
            ],
        ]);
    }

    private function buildWeightMatrix(): array
    {
        $links  = RegionExposureLink::all();
        $matrix = [];

        foreach ($links as $link) {
            $matrix[$link->source_region_id][$link->target_region_id] =
                min(1.0, max(0.0, $link->exposure_weight));
        }

        return $matrix;
    }

    private function computeCascadeIndex(array $baseline, array $final): float
    {
        $sum = 0.0;

        foreach ($baseline as $id => $vector) {
            $sum += abs($final[$id]['risk'] - $vector['risk']);
        }

        $avg = $sum / max(1, count($baseline));

        return round(min(1.0, $avg / 100.0), 4);
    }

    private function clampBounded(float $value): float
    {
        return max(0.0, min(100.0, $value));
    }
}
