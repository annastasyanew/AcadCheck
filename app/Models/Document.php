<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'user_id',
    'document_type_id',
    'title',
    'topic',
    'keywords',
    'description',
    'status',
    'latest_score',
    'latest_version_id',
])]
class Document extends Model
{
    public const STATUS_UPLOADED = 'uploaded';

    public const STATUS_ANALYZED = 'analyzed';

    public const STATUS_NEED_REVISION = 'need_revision';

    public const STATUS_READY = 'ready';

    public const STATUS_REVISED = 'revised';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function documentType(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class);
    }

    public function versions(): HasMany
    {
        return $this->hasMany(DocumentVersion::class);
    }

    public function latestVersion(): BelongsTo
    {
        return $this->belongsTo(DocumentVersion::class, 'latest_version_id');
    }

    public function analysisResults(): HasMany
    {
        return $this->hasMany(AnalysisResult::class);
    }

    public function reviewerComments(): HasMany
    {
        return $this->hasMany(ReviewerComment::class);
    }
}
