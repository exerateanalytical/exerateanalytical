<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ServiceAccessRecord extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'country_id', 'region_id', 'year',
        'electricity_access_percent', 'safe_water_access_percent',
        'road_density_km_per_100km2', 'paved_road_percent',
        'healthcare_facilities_total', 'healthcare_facilities_per_10000',
        'schools_total', 'schools_per_10000',
        'internet_penetration_percent', 'mobile_network_coverage_percent',
        'source_title', 'source_url', 'data_version',
    ];

    protected $casts = [
        'year' => 'integer',
        'electricity_access_percent' => 'decimal:2',
        'safe_water_access_percent' => 'decimal:2',
        'road_density_km_per_100km2' => 'decimal:2',
        'paved_road_percent' => 'decimal:2',
        'healthcare_facilities_total' => 'integer',
        'healthcare_facilities_per_10000' => 'decimal:2',
        'schools_total' => 'integer',
        'schools_per_10000' => 'decimal:2',
        'internet_penetration_percent' => 'decimal:2',
        'mobile_network_coverage_percent' => 'decimal:2',
        'data_version' => 'integer',
    ];

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }
}
