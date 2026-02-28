<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GovernanceInfluence extends Model
{
    use HasUuids;

    /** Immutable time-series snapshot — no updates. */
    public const UPDATED_AT = null;

    protected $fillable = [
        'region_id',
        'source_type',
        'source_id',
        'influence_type',
        'influence_score',
        'impact_direction',
        'calculated_at',
    ];

    protected $casts = [
        'influence_score' => 'float',
        'calculated_at'   => 'datetime',
    ];

    // ── Relations ─────────────────────────────────────────────────────────────

    public function region(): BelongsTo
    {
        return $this->belongsTo(FederationRegion::class, 'region_id');
    }

    // ── Display helpers ───────────────────────────────────────────────────────

    /**
     * Human-readable label for influence_type values.
     */
    public static function typeLabels(): array
    {
        return [
            'trust_shift'        => 'Trust Shift',
            'participation_gap'  => 'Participation Gap',
            'contagion_pressure' => 'Contagion Pressure',
        ];
    }
}
