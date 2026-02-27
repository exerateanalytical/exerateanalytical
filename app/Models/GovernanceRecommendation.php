<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GovernanceRecommendation extends Model
{
    use HasUuids;

    /**
     * No updated_at — recommendations are immutable once created.
     * Status transitions are the only mutations allowed.
     */
    public const UPDATED_AT = null;

    protected $fillable = [
        'recommendation_type',
        'severity',
        'title',
        'rationale',
        'suggested_action',
        'confidence_score',
        'source_snapshot_at',
        'status',
        'accepted_action_id',
        'expires_at',
    ];

    protected $casts = [
        'suggested_action'   => 'array',
        'confidence_score'   => 'float',
        'source_snapshot_at' => 'datetime',
        'expires_at'         => 'datetime',
    ];

    // ── Severity ordering ─────────────────────────────────────────────────────

    /** Numeric weight for ORDER BY severity DESC. */
    public static function severityWeight(): string
    {
        return "CASE severity
            WHEN 'critical' THEN 4
            WHEN 'high'     THEN 3
            WHEN 'medium'   THEN 2
            WHEN 'low'      THEN 1
            ELSE 0 END";
    }

    // ── Relations ─────────────────────────────────────────────────────────────

    public function acceptedAction(): BelongsTo
    {
        return $this->belongsTo(GovernanceAction::class, 'accepted_action_id');
    }
}
