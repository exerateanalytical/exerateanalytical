<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BiasMonitoring extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'country_id', 'administration_period', 'average_governance_score',
        'score_variance', 'methodology_version', 'analysis_date',
    ];

    protected $casts = [
        'average_governance_score' => 'decimal:4',
        'score_variance' => 'decimal:4',
        'methodology_version' => 'integer',
        'analysis_date' => 'datetime',
    ];

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }
}
