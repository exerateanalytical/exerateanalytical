<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RegionExposureLink extends Model
{
    use HasUuids;

    const UPDATED_AT = null;

    protected $fillable = [
        'source_region_id',
        'target_region_id',
        'exposure_weight',
    ];

    protected $casts = [
        'exposure_weight' => 'float',
    ];

    public function sourceRegion(): BelongsTo
    {
        return $this->belongsTo(FederationRegion::class, 'source_region_id');
    }

    public function targetRegion(): BelongsTo
    {
        return $this->belongsTo(FederationRegion::class, 'target_region_id');
    }
}
