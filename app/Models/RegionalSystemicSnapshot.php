<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RegionalSystemicSnapshot extends Model
{
    use HasUuids;

    /** Snapshots are immutable; no updated_at column. */
    const UPDATED_AT = null;

    protected $fillable = [
        'region_id',
        'region_code',
        'snapshot_at',
        'payload',
    ];

    protected $casts = [
        'snapshot_at' => 'datetime',
        'payload'     => 'array',
    ];

    public function federationRegion(): BelongsTo
    {
        return $this->belongsTo(FederationRegion::class, 'region_id');
    }
}
