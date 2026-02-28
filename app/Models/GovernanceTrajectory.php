<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GovernanceTrajectory extends Model
{
    use HasUuids;

    // Immutable time-series rows — no updated_at column
    const UPDATED_AT = null;

    protected $fillable = [
        'scope',
        'region_id',
        'stability_delta',
        'trust_delta',
        'participation_delta',
        'contagion_delta',
        'trajectory_score',
        'direction',
        'calculated_at',
    ];

    protected $casts = [
        'stability_delta'     => 'decimal:3',
        'trust_delta'         => 'decimal:3',
        'participation_delta' => 'decimal:3',
        'contagion_delta'     => 'decimal:3',
        'trajectory_score'    => 'decimal:3',
        'calculated_at'       => 'datetime',
        'created_at'          => 'datetime',
    ];

    public function region(): BelongsTo
    {
        return $this->belongsTo(FederationRegion::class, 'region_id');
    }
}
