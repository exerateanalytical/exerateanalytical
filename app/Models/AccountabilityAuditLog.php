<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountabilityAuditLog extends Model
{
    use HasUuids;

    protected $fillable = [
        'entity_id', 'previous_score', 'new_score', 'changed_by', 'changed_at',
    ];

    protected $casts = [
        'previous_score' => 'array',
        'new_score' => 'array',
        'changed_at' => 'datetime',
    ];

    public function entity(): BelongsTo
    {
        return $this->belongsTo(AccountabilityEntity::class, 'entity_id');
    }
}
