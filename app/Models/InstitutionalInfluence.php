<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstitutionalInfluence extends Model
{
    use HasUuids;

    // Immutable time-series snapshot — no updated_at column
    const UPDATED_AT = null;

    protected $fillable = [
        'actor_id',
        'region_id',
        'influence_category',
        'influence_score',
        'activity_volume',
        'trust_delta',
        'activity_breakdown',
        'calculated_at',
    ];

    protected $casts = [
        'influence_score'    => 'decimal:3',
        'trust_delta'        => 'decimal:3',
        'activity_breakdown' => 'array',
        'calculated_at'      => 'datetime',
    ];

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(FederationRegion::class, 'region_id');
    }

    /**
     * Human-readable labels for each influence category.
     */
    public static function categoryLabels(): array
    {
        return [
            'civic_leader'     => 'Civic Leader',
            'policy_driver'    => 'Policy Driver',
            'trust_stabilizer' => 'Trust Stabilizer',
            'volatility_source'=> 'Volatility Source',
        ];
    }
}
