<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SurveyQuestion extends Model
{
    use HasUuids;

    protected $fillable = [
        'survey_id', 'question_text', 'question_type', 'scale_min', 'scale_max', 'sort_order',
    ];

    protected $casts = [
        'scale_min' => 'integer',
        'scale_max' => 'integer',
        'sort_order' => 'integer',
    ];

    public function survey(): BelongsTo
    {
        return $this->belongsTo(Survey::class);
    }
}
