<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'reviewer_comment_id',
    'author_response',
    'revision_made',
    'revision_location',
    'revised_version_id',
])]
class ReviewerResponse extends Model
{
    public function reviewerComment(): BelongsTo
    {
        return $this->belongsTo(ReviewerComment::class);
    }

    public function revisedVersion(): BelongsTo
    {
        return $this->belongsTo(DocumentVersion::class, 'revised_version_id');
    }
}
