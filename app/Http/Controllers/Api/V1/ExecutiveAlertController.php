<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\RiskAlert;
use App\Models\RiskAlertEvent;
use App\Services\Risk\RiskAlertPersistenceService;
use App\Services\Risk\RiskAlertService;
use App\Services\Risk\RiskIntelligenceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ExecutiveAlertController extends Controller
{
    public function __construct(
        private readonly RiskIntelligenceService $riskService,
        private readonly RiskAlertService $alertService,
        private readonly RiskAlertPersistenceService $persistenceService,
    ) {}

    public function show(string $country): JsonResponse
    {
        $summary         = $this->riskService->getNationalRiskSummary($country);
        $evaluatedAlerts = $this->alertService->evaluate($summary);
        $alerts          = $this->persistenceService->sync($country, $evaluatedAlerts);

        return response()->json([
            'country_id'   => $country,
            'alert_count'  => count($alerts),
            'alerts'       => array_map(fn ($a) => [
                'id'              => $a['id'],
                'type'            => $a['type'],
                'severity'        => $a['severity'],
                'acknowledged_at' => $a['acknowledged_at'],
            ], $alerts),
            'last_updated' => $summary['last_updated'],
        ]);
    }

    public function acknowledge(string $id, Request $request): JsonResponse
    {
        $alert = RiskAlert::findOrFail($id);

        $alert->update([
            'acknowledged_at' => Carbon::now(),
            'acknowledged_by' => $request->input('acknowledged_by'),
        ]);

        RiskAlertEvent::create([
            'risk_alert_id' => $alert->id,
            'country_id'    => $alert->country_id,
            'type'          => $alert->type,
            'event_type'    => 'acknowledged',
            'severity'      => $alert->severity,
        ]);

        return response()->json([
            'id'              => $alert->id,
            'acknowledged_at' => $alert->acknowledged_at,
            'acknowledged_by' => $alert->acknowledged_by,
        ]);
    }
}
