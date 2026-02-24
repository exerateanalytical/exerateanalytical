<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApprovalRating extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'country_id', 'region_id', 'year', 'approve_percent', 'disapprove_percent',
        'neutral_percent', 'sample_size', 'margin_of_error', 'confidence_interval',
        'rolling_average_90_day', 'calculated_at',
    ];

    protected $casts = [
        'year' => 'integer',
        'approve_percent' => 'decimal:2',
        'disapprove_percent' => 'decimal:2',
        'neutral_percent' => 'decimal:2',
        'sample_size' => 'integer',
        'margin_of_error' => 'decimal:2',
        'rolling_average_90_day' => 'decimal:2',
        'calculated_at' => 'datetime',
    ];

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }
}
