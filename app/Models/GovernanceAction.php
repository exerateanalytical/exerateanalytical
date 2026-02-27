<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GovernanceAction extends Model
{
    use HasUuids;

    /**
     * Immutable record — no updated_at column on this table.
     */
    public const UPDATED_AT = null;

    protected $fillable = [
        'actor_id',
        'action_type',
        'target_id',
        'target_type',
        'parameters',
        'result_snapshot',
        'executed_at',
    ];

    protected $casts = [
        'parameters'      => 'array',
        'result_snapshot' => 'array',
        'executed_at'     => 'datetime',
    ];

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
