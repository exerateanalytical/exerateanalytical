<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DataAccessDisruption extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'country_id', 'year', 'disruption_type', 'description', 'flagged_at', 'severity',
    ];

    protected $casts = [
        'year' => 'integer',
        'flagged_at' => 'datetime',
    ];

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }
}
