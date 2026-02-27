<?php

namespace App\Services\Executive;

use App\Models\GovernanceAction;
use App\Models\User;
use App\Services\Civic\PolicyService;
use App\Services\Civic\PollService;
use App\Services\Risk\RiskContagionService;
use App\Services\Risk\RiskSimulationService;
use Illuminate\Support\Carbon;
use RuntimeException;

class GovernanceActionService
{
    public function __construct(
        private readonly RiskContagionService  $contagionService,
        private readonly RiskSimulationService $simulationService,
        private readonly PollService           $pollService,
        private readonly PolicyService         $policyService,
    ) {}

    /**
     * Execute a governance action on behalf of $actor, persist the record,
     * and return the persisted GovernanceAction.
     *
     * @throws RuntimeException for unknown or unrecoverable action types.
     */
    public function execute(User $actor, string $type, array $payload): GovernanceAction
    {
        $result = match ($type) {
            'simulate_contagion' => $this->runContagion($payload),
            'simulate_shock'     => $this->runShock($payload),
            'launch_poll'        => $this->runLaunchPoll($actor, $payload),
            'open_consultation'  => $this->runOpenConsultation($actor, $payload),
            'flag_region'        => $this->runFlagRegion($payload),
            default              => throw new RuntimeException("Unknown action type: {$type}"),
        };

        return GovernanceAction::create([
            'actor_id'        => $actor->id,
            'action_type'     => $type,
            'target_id'       => $payload['target_id']   ?? null,
            'target_type'     => $payload['target_type'] ?? null,
            'parameters'      => $payload,
            'result_snapshot' => $result,
            'executed_at'     => Carbon::now(),
        ]);
    }

    // ── Action runners ────────────────────────────────────────────────────────

    private function runContagion(array $payload): array
    {
        $run = $this->contagionService->execute(
            regionId:        $payload['region_id'],
            shockMagnitude:  (float) $payload['shock_magnitude'],
            contagionFactor: (float) ($payload['contagion_factor'] ?? 0.5),
            iterations:      (int)   ($payload['iterations']       ?? 5),
        );

        return $run->toArray();
    }

    private function runShock(array $payload): array
    {
        return $this->simulationService->run($payload);
    }

    private function runLaunchPoll(User $actor, array $payload): array
    {
        $poll = $this->pollService->createPoll($actor, $payload);

        return $poll->toArray();
    }

    private function runOpenConsultation(User $actor, array $payload): array
    {
        $proposal = $this->policyService->createProposal($actor, $payload);

        return $proposal->toArray();
    }

    private function runFlagRegion(array $payload): array
    {
        return [
            'flagged'    => true,
            'region_id'  => $payload['region_id'] ?? null,
            'reason'     => $payload['reason']    ?? null,
            'flagged_at' => Carbon::now()->toIso8601String(),
        ];
    }
}
