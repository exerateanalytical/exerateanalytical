<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ExposureMatrix extends Model
{
    use HasUuids;

    protected $fillable = [
        'name',
        'description',
        'matrix_json',
        'version',
        'active',
        'created_by',
        'region_id',
    ];

    protected $casts = [
        'matrix_json' => 'array',
        'active'      => 'boolean',
        'version'     => 'integer',
    ];
}
