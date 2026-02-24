<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RegionalEquityMetric extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'country_id', 'year',
        'electricity_variance', 'water_variance', 'road_density_variance',
        'healthcare_density_variance', 'education_density_variance',
        'digital_access_variance', 'equity_score', 'calculated_at',
    ];

    protected $casts = [
        'year' => 'integer',
        'electricity_variance' => 'decimal:4',
        'water_variance' => 'decimal:4',
        'road_density_variance' => 'decimal:4',
        'healthcare_density_variance' => 'decimal:4',
        'education_density_variance' => 'decimal:4',
        'digital_access_variance' => 'decimal:4',
        'equity_score' => 'decimal:2',
        'calculated_at' => 'datetime',
    ];

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }
}
