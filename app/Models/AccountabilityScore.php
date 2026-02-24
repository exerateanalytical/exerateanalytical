<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountabilityScore extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'accountability_entity_id', 'budget_execution_score', 'delivery_score',
        'service_impact_score', 'transparency_score', 'composite_accountability_score',
        'calculated_at',
    ];

    protected $casts = [
        'budget_execution_score' => 'decimal:2',
        'delivery_score' => 'decimal:2',
        'service_impact_score' => 'decimal:2',
        'transparency_score' => 'decimal:2',
        'composite_accountability_score' => 'decimal:2',
        'calculated_at' => 'datetime',
    ];

    public function entity(): BelongsTo
    {
        return $this->belongsTo(AccountabilityEntity::class, 'accountability_entity_id');
    }
}
