<?php

namespace App\Models;

use App\Enums\InstitutionType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Institution extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'country_id',
        'name',
        'type',
        'legal_mandate',
        'transparency_score',
    ];

    protected $casts = [
        'type' => InstitutionType::class,
        'transparency_score' => 'decimal:2',
    ];

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }
}
