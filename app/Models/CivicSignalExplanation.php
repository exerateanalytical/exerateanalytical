<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * CT-12 — Causality explanation snapshot attached to a CT-11 priority record.
 *
 * Immutable: each scheduler run may write a new row per priority; old rows are
 * preserved for audit. Only the latest row per signal_priority_id is served.
 */
class CivicSignalExplanation extends Model
{
    use HasUuids;

    /** Table only has `created_at` (useCurrent). */
    public $timestamps = false;

    protected $fillable = [
        'signal_priority_id',
        'primary_driver',
        'driver_breakdown',
        'affected_regions',
        'trajectory_direction',
        'projected_risk_level',
        'explanation_summary',
        'created_at',
    ];

    protected $casts = [
        'driver_breakdown' => 'array',
        'affected_regions' => 'array',
        'created_at'       => 'datetime',
    ];

    public function signalPriority(): BelongsTo
    {
        return $this->belongsTo(CivicSignalPriority::class, 'signal_priority_id');
    }
}
