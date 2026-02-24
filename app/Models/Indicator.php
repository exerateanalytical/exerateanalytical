<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Indicator extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'pillar_id', 'country_id', 'name', 'description', 'unit', 'weight',
        'normalization_method', 'data_type', 'reliability_level',
        'reporting_lag_months', 'is_active', 'version',
    ];

    protected $casts = [
        'weight' => 'decimal:2',
        'is_active' => 'boolean',
        'version' => 'integer',
        'reporting_lag_months' => 'integer',
    ];

    public function pillar(): BelongsTo
    {
        return $this->belongsTo(Pillar::class);
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function values(): HasMany
    {
        return $this->hasMany(IndicatorValue::class);
    }

    public function reliabilityScore(): HasOne
    {
        return $this->hasOne(IndicatorReliabilityScore::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForPillarVersion($query, string $pillarId, int $version)
    {
        return $query->where('pillar_id', $pillarId)->where('version', $version)->active();
    }
}
