<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GovernanceScore extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'country_id', 'region_id', 'year', 'pillar_scores', 'composite_score',
        'version', 'calculated_at', 'calculated_by',
    ];

    protected $casts = [
        'pillar_scores' => 'array',
        'composite_score' => 'decimal:2',
        'version' => 'integer',
        'year' => 'integer',
        'calculated_at' => 'datetime',
    ];

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function scopeNational($query)
    {
        return $query->whereNull('region_id');
    }

    public function scopeForCountryYear($query, string $countryId, int $year)
    {
        return $query->where('country_id', $countryId)->where('year', $year);
    }
}
