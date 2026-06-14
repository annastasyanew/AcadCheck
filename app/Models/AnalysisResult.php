<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'document_id',
    'document_version_id',
    'total_score',
    'status',
    'summary',
    'main_issues',
    'recommendations',
    'revision_priorities',
    'raw_ai_response',
])]
class AnalysisResult extends Model
{
    protected function casts(): array
    {
        return [
            'main_issues' => 'array',
            'recommendations' => 'array',
            'revision_priorities' => 'array',
            'raw_ai_response' => 'array',
        ];
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function documentVersion(): BelongsTo
    {
        return $this->belongsTo(DocumentVersion::class);
    }

    public function aspectScores(): HasMany
    {
        return $this->hasMany(AnalysisAspectScore::class);
    }
}
