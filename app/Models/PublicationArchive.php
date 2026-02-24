<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PublicationArchive extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'country_id', 'year', 'report_type', 'file_path', 'file_hash', 'is_locked', 'published_at',
    ];

    protected $casts = [
        'year' => 'integer',
        'is_locked' => 'boolean',
        'published_at' => 'datetime',
    ];

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }
}
