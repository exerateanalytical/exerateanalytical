<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class RiskContagionRun extends Model
{
    use HasUuids;

    const UPDATED_AT = null;

    protected $fillable = [
        'executed_at',
        'baseline_vector',
        'final_vector',
        'cascade_index',
        'iteration_count',
        'parameters',
    ];

    protected $casts = [
        'executed_at'     => 'datetime',
        'baseline_vector' => 'array',
        'final_vector'    => 'array',
        'cascade_index'   => 'float',
        'iteration_count' => 'integer',
        'parameters'      => 'array',
    ];
}
