<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PollTemplate extends Model
{
    use HasUuids;

    protected $fillable = [
        'title',
        'description',
        'category',
        'poll_type',
        'options',
        'allow_multiple_votes',
        'verified_only',
        'is_premium',
        'sort_order',
        'created_by',
    ];

    protected $casts = [
        'options'              => 'array',
        'allow_multiple_votes' => 'boolean',
        'verified_only'        => 'boolean',
        'is_premium'           => 'boolean',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
