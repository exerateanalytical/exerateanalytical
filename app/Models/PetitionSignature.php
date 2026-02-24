<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PetitionSignature extends Model
{
    use HasUuids;

    protected $fillable = ['petition_id', 'ip_hash', 'region_id', 'signed_at'];

    protected $casts = [
        'signed_at' => 'datetime',
    ];

    public function petition(): BelongsTo
    {
        return $this->belongsTo(Petition::class);
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }
}
