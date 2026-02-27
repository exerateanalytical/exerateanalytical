<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReputationEvent extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'user_id',
        'event_type',
        'delta',
        'context_type',
        'context_id',
    ];

    protected $casts = [
        'delta' => 'decimal:3',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
