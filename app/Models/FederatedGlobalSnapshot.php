<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class FederatedGlobalSnapshot extends Model
{
    use HasUuids;

    /** Snapshots are immutable; no updated_at column. */
    const UPDATED_AT = null;

    protected $fillable = [
        'snapshot_at',
        'region_count',
        'payload',
    ];

    protected $casts = [
        'snapshot_at'  => 'datetime',
        'region_count' => 'integer',
        'payload'      => 'array',
    ];
}
