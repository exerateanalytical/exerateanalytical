<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FiscalYear extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = ['country_id', 'year', 'currency', 'exchange_rate_to_usd', 'is_locked'];

    protected $casts = [
        'year' => 'integer',
        'exchange_rate_to_usd' => 'decimal:6',
        'is_locked' => 'boolean',
    ];

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }
}
