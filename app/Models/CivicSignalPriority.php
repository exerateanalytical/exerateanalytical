<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * CT-11 — Immutable snapshot of a civic signal's computed priority score.
 *
 * The table only has `created_at` (via useCurrent), no `updated_at`.
 * Each scheduler run appends new rows; records are never mutated.
 */
class CivicSignalPriority extends Model
{
    use HasUuids;

    /** Disable automatic timestamp management — table only has `created_at`. */
    public $timestamps = false;

    protected $fillable = [
        'signal_type',
        'signal_id',
        'priority_score',
        'participation_velocity',
        'cross_region_factor',
        'trust_impact',
        'calculated_at',
        'created_at',
    ];

    protected $casts = [
        'priority_score'         => 'decimal:3',
        'participation_velocity' => 'decimal:3',
        'cross_region_factor'    => 'decimal:3',
        'trust_impact'           => 'decimal:3',
        'calculated_at'          => 'datetime',
        'created_at'             => 'datetime',
    ];
}
