<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class RiskAlert extends Model
{
    use HasUuids;

    protected $fillable = [
        'country_id',
        'region_id',
        'type',
        'severity',
        'active',
        'acknowledged_at',
        'acknowledged_by',
        'first_triggered_at',
        'last_triggered_at',
    ];

    protected $casts = [
        'active'             => 'boolean',
        'acknowledged_at'    => 'datetime',
        'first_triggered_at' => 'datetime',
        'last_triggered_at'  => 'datetime',
    ];
}
