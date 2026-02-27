<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RepresentationMetric extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'region_id',
        'eligible_population',
        'active_users',
        'verified_users',
        'participation_rate',
        'representation_gap',
        'calculated_at',
    ];

    protected $casts = [
        'calculated_at'      => 'datetime',
        'participation_rate' => 'decimal:3',
        'representation_gap' => 'decimal:3',
    ];

    public function region(): BelongsTo
    {
        return $this->belongsTo(FederationRegion::class, 'region_id');
    }
}
