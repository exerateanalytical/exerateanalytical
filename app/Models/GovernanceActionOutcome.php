<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * CT-14 — Immutable outcome measurement row.
 *
 * One row per (governance_action_id, observation_window) pair.
 * Created exactly once and never updated.
 */
class GovernanceActionOutcome extends Model
{
    use HasUuids;

    /** Immutable — no updated_at column. */
    public $timestamps = false;

    protected $fillable = [
        'governance_action_id',
        'observation_window',
        'stability_delta',
        'trust_delta',
        'participation_delta',
        'cascade_delta',
        'effectiveness_score',
        'measured_at',
    ];

    protected $casts = [
        'stability_delta'     => 'float',
        'trust_delta'         => 'float',
        'participation_delta' => 'float',
        'cascade_delta'       => 'float',
        'effectiveness_score' => 'float',
        'measured_at'         => 'datetime',
        'created_at'          => 'datetime',
    ];

    public function action(): BelongsTo
    {
        return $this->belongsTo(GovernanceAction::class, 'governance_action_id');
    }
}
