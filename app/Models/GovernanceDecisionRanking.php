<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GovernanceDecisionRanking extends Model
{
    use HasUuids;

    /** Immutable once created — rankings are replaced wholesale per recommendation cycle. */
    public $timestamps = false;

    protected $fillable = [
        'recommendation_id',
        'scenario_type',
        'projected_stability_delta',
        'projected_trust_delta',
        'projected_cascade_delta',
        'projected_participation_delta',
        'decision_score',
        'rank_position',
    ];

    protected $casts = [
        'projected_stability_delta'     => 'float',
        'projected_trust_delta'         => 'float',
        'projected_cascade_delta'       => 'float',
        'projected_participation_delta' => 'float',
        'decision_score'                => 'float',
        'rank_position'                 => 'integer',
    ];

    public function recommendation(): BelongsTo
    {
        return $this->belongsTo(GovernanceRecommendation::class, 'recommendation_id');
    }
}
