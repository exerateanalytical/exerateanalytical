<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Region extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'country_id',
        'name',
        'administrative_level',
        'population',
        'area_km2',
        'parent_region_id',
    ];

    protected $casts = [
        'population' => 'integer',
        'area_km2' => 'decimal:4',
        'administrative_level' => 'integer',
    ];

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function parentRegion(): BelongsTo
    {
        return $this->belongsTo(Region::class, 'parent_region_id');
    }

    public function childRegions(): HasMany
    {
        return $this->hasMany(Region::class, 'parent_region_id');
    }

    public function scopeByCountry($query, string $countryId)
    {
        return $query->where('country_id', $countryId);
    }
}
