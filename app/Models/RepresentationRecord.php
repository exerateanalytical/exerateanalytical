<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RepresentationRecord extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'country_id', 'region_id', 'year', 'gender_distribution', 'regional_distribution',
        'age_distribution', 'professional_background_distribution', 'equity_index_score',
    ];

    protected $casts = [
        'year' => 'integer',
        'gender_distribution' => 'array',
        'regional_distribution' => 'array',
        'age_distribution' => 'array',
        'professional_background_distribution' => 'array',
        'equity_index_score' => 'decimal:2',
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
