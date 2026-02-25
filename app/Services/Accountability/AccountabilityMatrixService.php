<?php

namespace App\Services\Accountability;

use App\Models\AccountabilityAuditLog;
use App\Models\AccountabilityEntity;
use App\Models\AccountabilityScore;
use App\Models\BudgetAllocation;
use App\Models\RiskSignal;
use App\Models\ServiceAccessRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AccountabilityMatrixService
{
    public function calculateBudgetExecutionScore(string $entityId): float
    {
        $entity = AccountabilityEntity::with('links')->findOrFail($entityId);

        $budgetIds = $entity->links->pluck('linked_budget_id')->filter();
        if ($budgetIds->isEmpty()) {
            Log::warning('No linked budgets for accountability entity', ['entity_id' => $entityId]);
            return 0.0;
        }

        $allocations = BudgetAllocation::whereIn('id', $budgetIds)->get();
        if ($allocations->isEmpty()) return 0.0;

        $avgExecutionRate = $allocations->avg(fn ($a) => (float) $a->execution_rate);

        return round(min(100, max(0, $avgExecutionRate)), 2);
    }

    public function calculateDeliveryScore(string $entityId): float
    {
        // Delivery score based on budget execution + time flags
        $entity = AccountabilityEntity::with('links')->findOrFail($entityId);
        $budgetIds = $entity->links->pluck('linked_budget_id')->filter();

        if ($budgetIds->isEmpty()) return 0.0;

        $allocations = BudgetAllocation::whereIn('id', $budgetIds)->get();
        $total = $allocations->count();
        if ($total === 0) return 0.0;

        $delayed = $allocations->where('delay_flag', true)->count();
        $completionRatio = ($total - $delayed) / $total;

        return round($completionRatio * 100, 2);
    }

    public function calculateServiceImpactScore(string $entityId): float
    {
        $entity = AccountabilityEntity::findOrFail($entityId);

        if (!$entity->region_id) {
            Log::info('No region for service impact, using national data', ['entity_id' => $entityId]);
            return 50.0; // Default neutral score when no regional data
        }

        $currentRecord = ServiceAccessRecord::where('region_id', $entity->region_id)
            ->where('year', $entity->year)
            ->first();

        $previousRecord = ServiceAccessRecord::where('region_id', $entity->region_id)
            ->where('year', $entity->year - 1)
            ->first();

        if (!$currentRecord || !$previousRecord) {
            Log::warning('Insufficient historical data for service impact', ['entity_id' => $entityId]);
            return 0.0;
        }

        $metrics = [
            'electricity_access_percent',
            'safe_water_access_percent',
            'internet_penetration_percent',
        ];

        $changes = [];
        foreach ($metrics as $metric) {
            $prev = (float) ($previousRecord->$metric ?? 0);
            $curr = (float) ($currentRecord->$metric ?? 0);
            if ($prev > 0) {
                $changes[] = (($curr - $prev) / $prev) * 100;
            }
        }

        if (empty($changes)) return 50.0;

        $avgChange = array_sum($changes) / count($changes);
        $score = min(100, max(0, 50 + $avgChange));

        return round($score, 2);
    }

    public function calculateCompositeAccountability(string $entityId): AccountabilityScore
    {
        $budgetScore = $this->calculateBudgetExecutionScore($entityId);
        $deliveryScore = $this->calculateDeliveryScore($entityId);
        $serviceScore = $this->calculateServiceImpactScore($entityId);

        $entity = AccountabilityEntity::with(['institution' => fn ($q) => $q->withTrashed()])
            ->findOrFail($entityId);
        $transparencyScore = (float) ($entity->institution?->transparency_score ?? 50);

        $composite = ($budgetScore * 0.3) + ($deliveryScore * 0.3) + ($serviceScore * 0.3) + ($transparencyScore * 0.1);

        $previousScore = AccountabilityScore::where('accountability_entity_id', $entityId)->latest()->first();

        $score = DB::transaction(function () use (
            $entityId, $entity, $budgetScore, $deliveryScore,
            $serviceScore, $transparencyScore, $composite, $previousScore
        ) {
            $score = AccountabilityScore::updateOrCreate(
                ['accountability_entity_id' => $entityId],
                [
                    'budget_execution_score' => $budgetScore,
                    'delivery_score' => $deliveryScore,
                    'service_impact_score' => $serviceScore,
                    'transparency_score' => $transparencyScore,
                    'composite_accountability_score' => round($composite, 2),
                    'calculated_at' => now(),
                ]
            );

            if ($previousScore) {
                AccountabilityAuditLog::create([
                    'entity_id' => $entityId,
                    'previous_score' => $previousScore->toArray(),
                    'new_score' => $score->toArray(),
                    'changed_by' => auth()->id(),
                    'changed_at' => now(),
                ]);

                $prevComposite = (float) $previousScore->composite_accountability_score;
                if ($prevComposite > 0) {
                    $dropPercent = (($prevComposite - $composite) / $prevComposite) * 100;
                    if ($dropPercent > 15) {
                        RiskSignal::create([
                            'country_id' => $entity->country_id,
                            'signal_type' => 'accountability_score_drop',
                            'severity' => 'high',
                            'module' => 'Accountability',
                            'description' => "Composite accountability score dropped by " . round($dropPercent, 2) . "% YoY.",
                            'triggered_at' => now(),
                        ]);
                    }
                }
            }

            return $score;
        });

        return $score;
    }
}
