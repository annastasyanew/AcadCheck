<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'analysis_result_id',
    'aspect_name',
    'score',
    'status',
    'finding',
    'recommendation',
])]
class AnalysisAspectScore extends Model
{
    public function analysisResult(): BelongsTo
    {
        return $this->belongsTo(AnalysisResult::class);
    }
}
