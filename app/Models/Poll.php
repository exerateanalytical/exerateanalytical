<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Poll extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'creator_id',
        'region_id',
        'title',
        'description',
        'visibility',
        'type',
        'options',
        'starts_at',
        'ends_at',
        'allow_multiple_votes',
        'verified_only',
        'total_votes',
        'status',
    ];

    protected $casts = [
        'options'              => 'array',
        'starts_at'            => 'datetime',
        'ends_at'              => 'datetime',
        'allow_multiple_votes' => 'boolean',
        'verified_only'        => 'boolean',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(FederationRegion::class, 'region_id');
    }

    public function votes(): HasMany
    {
        return $this->hasMany(PollVote::class);
    }
}
