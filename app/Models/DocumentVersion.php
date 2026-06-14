<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'document_id',
    'version_number',
    'file_path',
    'file_original_name',
    'file_type',
    'file_size',
    'extracted_text',
    'revision_note',
    'uploaded_at',
])]
class DocumentVersion extends Model
{
    protected function casts(): array
    {
        return [
            'uploaded_at' => 'datetime',
        ];
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function analysisResults(): HasMany
    {
        return $this->hasMany(AnalysisResult::class);
    }

    public function reviewerResponses(): HasMany
    {
        return $this->hasMany(ReviewerResponse::class, 'revised_version_id');
    }
}
