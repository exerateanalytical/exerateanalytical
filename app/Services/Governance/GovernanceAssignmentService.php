<?php

namespace App\Services\Governance;

use App\Models\GovernanceAction;
use App\Models\GovernanceAssignment;
use App\Models\User;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

/**
 * CT-15 Institutional Coordination Engine — assignment lifecycle service.
 *
 * Rules:
 *  - progress_percent cannot decrease
 *  - complete() always sets progress to 100
 *  - blocked status may be applied from any state at any time
 *  - all state transitions are idempotent (re-applying the same state is a no-op)
 */
class GovernanceAssignmentService
{
    /**
     * Delegate a governance action to a named institution.
     */
    public function assignAction(
        GovernanceAction $action,
        string $institution,
        User $assigner,
    ): GovernanceAssignment {
        return GovernanceAssignment::create([
            'governance_action_id' => $action->id,
            'institution_name'     => $institution,
            'assigned_by'          => $assigner->id,
            'status'               => 'assigned',
            'progress_percent'     => 0,
        ]);
    }

    /**
     * Mark an assignment as acknowledged by the institution.
     *
     * Idempotent: if already acknowledged (or further along), returns as-is.
     */
    public function acknowledge(GovernanceAssignment $assignment): GovernanceAssignment
    {
        if ($assignment->acknowledged_at !== null) {
            return $assignment;
        }

        $assignment->status          = 'acknowledged';
        $assignment->acknowledged_at = Carbon::now();
        $assignment->save();

        return $assignment;
    }

    /**
     * Update progress on an in-flight assignment.
     *
     * Rules enforced:
     *  - progress_percent cannot decrease (lower values are ignored)
     *  - first progress update on assigned/acknowledged transitions to in_progress
     *  - updates on completed assignments are no-ops (idempotent)
     *  - passing $blocked = true sets status to 'blocked' regardless of percent
     *
     * @throws InvalidArgumentException if percent is out of bounds
     */
    public function updateProgress(
        GovernanceAssignment $assignment,
        int $percent,
        ?string $notes = null,
        bool $blocked = false,
    ): GovernanceAssignment {
        if ($blocked) {
            $assignment->status = 'blocked';
            if ($notes !== null) {
                $assignment->notes = $notes;
            }
            $assignment->save();
            return $assignment;
        }

        if ($percent < 0 || $percent > 100) {
            throw new InvalidArgumentException("progress_percent must be between 0 and 100.");
        }

        // Completed assignments are immutable
        if ($assignment->status === 'completed') {
            return $assignment;
        }

        // Progress cannot decrease
        if ($percent < $assignment->progress_percent) {
            return $assignment;
        }

        $assignment->progress_percent = $percent;

        if (in_array($assignment->status, ['assigned', 'acknowledged', 'blocked'])) {
            $assignment->status = 'in_progress';
        }

        if ($notes !== null) {
            $assignment->notes = $notes;
        }

        $assignment->save();

        return $assignment;
    }

    /**
     * Mark an assignment as completed.
     *
     * Sets progress to 100 and records completed_at timestamp.
     * Idempotent: re-completing an already-completed assignment is a no-op.
     */
    public function complete(GovernanceAssignment $assignment): GovernanceAssignment
    {
        if ($assignment->status === 'completed') {
            return $assignment;
        }

        $assignment->status           = 'completed';
        $assignment->progress_percent = 100;
        $assignment->completed_at     = Carbon::now();
        $assignment->save();

        return $assignment;
    }
}
