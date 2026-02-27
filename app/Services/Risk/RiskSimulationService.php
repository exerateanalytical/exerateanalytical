<?php

namespace App\Services\Risk;

/**
 * Shock-simulation stub.
 *
 * Full implementation is deferred to a future sprint.
 * GovernanceActionService calls run() for the simulate_shock action type;
 * this stub returns a typed marker that is stored in result_snapshot so the
 * action is still persisted and auditable.
 */
class RiskSimulationService
{
    public function run(array $params): array
    {
        return [
            'status'       => 'simulated',
            'engine'       => 'stub',
            'region_id'    => $params['region_id']       ?? null,
            'shock_vector' => $params['shock_vector']    ?? null,
            'note'         => 'Full simulation engine pending implementation.',
        ];
    }
}
