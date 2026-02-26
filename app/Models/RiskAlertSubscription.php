<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class RiskAlertSubscription extends Model
{
    use HasUuids;

    protected $fillable = [
        'country_id',
        'region_id',
        'alert_type',
        'severity',
        'channel',
        'channel_config',
        'active',
    ];

    protected $casts = [
        'channel_config' => 'array',
        'active'         => 'boolean',
    ];
}
