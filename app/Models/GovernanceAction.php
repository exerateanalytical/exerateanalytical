<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class GovernanceAction extends Model
{
    use HasUuids;

    /**
     * Immutable record — no updated_at column on this table.
     */
    public const UPDATED_AT = null;

    protected $fillable = [
        'actor_id',
        'action_type',
        'target_id',
        'target_type',
        'parameters',
        'result_snapshot',
        'executed_at',
        'status',
        'proposed_by',
        'approved_by',
        'approved_at',
        'notes',
    ];

    protected $casts = [
        'parameters'      => 'array',
        'result_snapshot' => 'array',
        'executed_at'     => 'datetime',
        'approved_at'     => 'datetime',
    ];

    // ── Relations ─────────────────────────────────────────────────────────────

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function proposer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'proposed_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // ── Lifecycle helpers ─────────────────────────────────────────────────────

    /**
     * Stamp approval on this action (does NOT execute — that is handled by
     * GovernanceWorkflowService::approveAction() inside a DB transaction).
     */
    public function approve(User $user): void
    {
        $this->approved_by  = $user->id;
        $this->approved_at  = Carbon::now();
        $this->status       = 'approved';
        $this->save();
    }

    /**
     * Mark the action as rejected and record the reason in notes.
     */
    public function reject(User $user, string $reason): void
    {
        $this->approved_by = $user->id;   // records who reviewed it
        $this->approved_at = Carbon::now();
        $this->status      = 'rejected';
        $this->notes       = $reason;
        $this->save();
    }
}
