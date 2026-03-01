<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * CT-15 — Institutional coordination assignment.
 *
 * Tracks delegation of a governance action to a named institution with
 * lifecycle status, progress tracking, and optional notes.
 */
class GovernanceAssignment extends Model
{
    use HasUuids;

    /** No updated_at — status mutations are recorded via timestamped columns. */
    public const UPDATED_AT = null;

    protected $fillable = [
        'governance_action_id',
        'institution_name',
        'assigned_by',
        'status',
        'progress_percent',
        'notes',
        'acknowledged_at',
        'completed_at',
    ];

    protected $casts = [
        'progress_percent' => 'integer',
        'acknowledged_at'  => 'datetime',
        'completed_at'     => 'datetime',
        'created_at'       => 'datetime',
    ];

    public function action(): BelongsTo
    {
        return $this->belongsTo(GovernanceAction::class, 'governance_action_id');
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}
