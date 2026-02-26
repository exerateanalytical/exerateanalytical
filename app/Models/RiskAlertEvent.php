<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class RiskAlertEvent extends Model
{
    use HasUuids;

    /** This table has no updated_at column. */
    const UPDATED_AT = null;

    protected $fillable = [
        'risk_alert_id',
        'country_id',
        'region_id',
        'type',
        'event_type',
        'severity',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];
}
