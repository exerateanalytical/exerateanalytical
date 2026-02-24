<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FiscalRiskSignal extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'country_id', 'year', 'risk_type', 'severity', 'description', 'triggered_at',
    ];

    protected $casts = [
        'year' => 'integer',
        'triggered_at' => 'datetime',
    ];

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }
}
