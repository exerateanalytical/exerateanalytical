<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RiskSignal extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'country_id', 'signal_type', 'severity', 'description', 'module',
        'metadata', 'is_escalated', 'triggered_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'is_escalated' => 'boolean',
        'triggered_at' => 'datetime',
    ];

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }
}
