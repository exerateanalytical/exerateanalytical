<?php

namespace App\Services\Governance;

use App\Models\GovernanceAction;
use App\Models\User;
use App\Services\Executive\GovernanceActionService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class GovernanceWorkflowService
{
    public function __construct(
        private readonly GovernanceActionService $actionService,
    ) {}

    /**
     * Create a governance action record in 'proposed' status without executing it.
     * The action sits pending approval until approveAction() or rejectAction() is called.
     */
    public function proposeAction(User $proposer, string $type, array $payload): GovernanceAction
    {
        return GovernanceAction::create([
            'actor_id'        => $proposer->id,
            'action_type'     => $type,
            'target_id'       => $payload['target_id']   ?? null,
            'target_type'     => $payload['target_type'] ?? null,
            'parameters'      => $payload,
            'result_snapshot' => null,
            'executed_at'     => null,
            'status'          => 'proposed',
            'proposed_by'     => $proposer->id,
        ]);
    }

    /**
     * Approve a pending action and immediately execute it inside a DB transaction.
     *
     * Execution is delegated to the existing GovernanceActionService::execute() pipeline.
     * The action's status is updated to 'executed' on success.
     *
     * @throws RuntimeException if the action is not in 'proposed' status.
     */
    public function approveAction(User $approver, GovernanceAction $action): GovernanceAction
    {
        if ($action->status !== 'proposed') {
            throw new RuntimeException(
                "Cannot approve action {$action->id}: current status is '{$action->status}'."
            );
        }

        return DB::transaction(function () use ($approver, $action) {
            // Record approval meta
            $action->approved_by = $approver->id;
            $action->approved_at = Carbon::now();
            $action->status      = 'approved';
            $action->save();

            // Execute through the existing pipeline using the original actor
            $executed = $this->actionService->execute(
                $action->actor,                // original proposer becomes the actor
                $action->action_type,
                $action->parameters ?? [],
            );

            // Carry forward the approval metadata to the executed record
            $executed->approved_by = $approver->id;
            $executed->approved_at = $action->approved_at;
            $executed->proposed_by = $action->proposed_by;
            $executed->notes       = $action->notes;
            $executed->save();

            // Archive the original proposal so audit trail is preserved
            $action->status = 'archived';
            $action->save();

            return $executed;
        });
    }

    /**
     * Reject a pending action, recording the reviewer and reason.
     *
     * @throws RuntimeException if the action is not in 'proposed' status.
     */
    public function rejectAction(User $rejector, GovernanceAction $action, string $reason): GovernanceAction
    {
        if ($action->status !== 'proposed') {
            throw new RuntimeException(
                "Cannot reject action {$action->id}: current status is '{$action->status}'."
            );
        }

        $action->reject($rejector, $reason);

        return $action->fresh();
    }
}
