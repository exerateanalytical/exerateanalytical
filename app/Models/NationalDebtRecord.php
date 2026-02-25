<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NationalDebtRecord extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'country_id', 'year', 'total_debt', 'debt_to_gdp_ratio', 'external_debt',
        'domestic_debt', 'debt_service_total', 'debt_service_ratio',
        'interest_payments', 'interest_as_budget_percent',
        'source_title', 'source_url', 'data_version', 'created_by',
    ];

    protected $casts = [
        'year' => 'integer',
        'total_debt' => 'decimal:2',
        'debt_to_gdp_ratio' => 'decimal:2',
        'external_debt' => 'decimal:2',
        'domestic_debt' => 'decimal:2',
        'debt_service_total' => 'decimal:2',
        'debt_service_ratio' => 'decimal:2',
        'interest_payments' => 'decimal:2',
        'interest_as_budget_percent' => 'decimal:2',
        'data_version' => 'integer',
    ];

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function scopeForCountryYear($query, string $countryId, int $year)
    {
        return $query->where('country_id', $countryId)->where('year', $year);
    }
}
