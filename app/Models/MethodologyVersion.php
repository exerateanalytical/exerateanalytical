<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MethodologyVersion extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'country_id', 'version_number', 'description_of_change', 'change_rationale',
        'impact_summary', 'is_active', 'published_at', 'created_by',
    ];

    protected $casts = [
        'version_number' => 'integer',
        'is_active' => 'boolean',
        'published_at' => 'datetime',
    ];

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
