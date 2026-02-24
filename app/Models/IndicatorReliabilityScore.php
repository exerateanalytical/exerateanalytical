<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IndicatorReliabilityScore extends Model
{
    use HasUuids;

    protected $fillable = [
        'indicator_id', 'reliability_level', 'confidence_notes',
        'reporting_lag_months', 'verification_status', 'last_reviewed_at',
    ];

    protected $casts = [
        'reporting_lag_months' => 'integer',
        'last_reviewed_at' => 'datetime',
    ];

    public function indicator(): BelongsTo
    {
        return $this->belongsTo(Indicator::class);
    }
}
