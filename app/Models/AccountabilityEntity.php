<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class AccountabilityEntity extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'country_id', 'institution_id', 'region_id', 'year',
        'mandate_area', 'legal_basis_reference',
    ];

    protected $casts = [
        'year' => 'integer',
    ];

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function links(): HasMany
    {
        return $this->hasMany(AccountabilityLink::class);
    }

    public function score(): HasOne
    {
        return $this->hasOne(AccountabilityScore::class);
    }

    public function auditLog(): HasMany
    {
        return $this->hasMany(AccountabilityAuditLog::class, 'entity_id');
    }
}
