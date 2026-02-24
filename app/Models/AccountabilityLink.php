<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountabilityLink extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'accountability_entity_id', 'linked_budget_id', 'linked_project_id',
        'linked_indicator_id', 'linked_policy_id',
    ];

    public function entity(): BelongsTo
    {
        return $this->belongsTo(AccountabilityEntity::class, 'accountability_entity_id');
    }
}
