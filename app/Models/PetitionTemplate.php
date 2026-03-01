<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PetitionTemplate extends Model
{
    use HasUuids;

    protected $fillable = [
        'title',
        'description',
        'category',
        'summary_template',
        'body_template',
        'default_signature_goal',
        'is_premium',
        'sort_order',
        'created_by',
    ];

    protected $casts = [
        'is_premium'             => 'boolean',
        'default_signature_goal' => 'integer',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
