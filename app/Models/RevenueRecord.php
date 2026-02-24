<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RevenueRecord extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'country_id', 'year', 'total_revenue', 'tax_revenue', 'non_tax_revenue',
        'grants', 'revenue_to_gdp_ratio', 'source_title', 'source_url',
    ];

    protected $casts = [
        'year' => 'integer',
        'total_revenue' => 'decimal:2',
        'tax_revenue' => 'decimal:2',
        'non_tax_revenue' => 'decimal:2',
        'grants' => 'decimal:2',
        'revenue_to_gdp_ratio' => 'decimal:2',
    ];

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }
}
