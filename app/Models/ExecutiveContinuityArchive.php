<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExecutiveContinuityArchive extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = ['document_type', 'file_path', 'file_hash', 'uploaded_at'];

    protected $casts = [
        'uploaded_at' => 'datetime',
    ];
}
