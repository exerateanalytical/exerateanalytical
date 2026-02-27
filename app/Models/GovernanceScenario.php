<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GovernanceScenario extends Model
{
    use HasUuids;

    /** No updated_at — scenarios are regenerated wholesale on each advisor cycle. */
    public const UPDATED_AT = null;

    protected $fillable = [
        'recommendation_id',
        'scenario_type',
        'projection_payload',
        'projected_stability',
        'projected_cascade_index',
        'projected_trust_delta',
        'confidence',
    ];

    protected $casts = [
        'projection_payload'      => 'array',
        'projected_stability'     => 'float',
        'projected_cascade_index' => 'float',
        'projected_trust_delta'   => 'float',
        'confidence'              => 'float',
    ];

    // ── Ordering helper ───────────────────────────────────────────────────────

    /**
     * Canonical display order: accept first (best outcome), delayed second, ignore last.
     */
    public static function typeOrder(): string
    {
        return "CASE scenario_type
            WHEN 'accept_action'  THEN 1
            WHEN 'delayed_action' THEN 2
            WHEN 'ignore_action'  THEN 3
            ELSE 4 END";
    }

    // ── Relations ─────────────────────────────────────────────────────────────

    public function recommendation(): BelongsTo
    {
        return $this->belongsTo(GovernanceRecommendation::class, 'recommendation_id');
    }
}
