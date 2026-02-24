<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IndicatorValue extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'indicator_id', 'country_id', 'region_id', 'year', 'raw_value',
        'normalized_value', 'source_title', 'source_url', 'source_document',
        'data_version', 'created_by',
    ];

    protected $casts = [
        'year' => 'integer',
        'raw_value' => 'decimal:6',
        'normalized_value' => 'decimal:6',
        'data_version' => 'integer',
    ];

    public function indicator(): BelongsTo
    {
        return $this->belongsTo(Indicator::class);
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function scopeForCountryYear($query, string $countryId, int $year)
    {
        return $query->where('country_id', $countryId)->where('year', $year);
    }
}
