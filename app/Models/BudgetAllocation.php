<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BudgetAllocation extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'country_id', 'region_id', 'year', 'sector_name', 'allocated_amount',
        'executed_amount', 'source_title', 'source_url',
    ];

    protected $casts = [
        'year' => 'integer',
        'allocated_amount' => 'decimal:2',
        'executed_amount' => 'decimal:2',
        'execution_rate' => 'decimal:2',
        'delay_flag' => 'boolean',
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
