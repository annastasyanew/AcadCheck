<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'document_id',
    'reviewer_label',
    'comment_number',
    'original_comment',
    'related_section',
    'priority',
    'status',
])]
class ReviewerComment extends Model
{
    public const PRIORITY_MINOR = 'minor';

    public const PRIORITY_MAJOR = 'major';

    public const PRIORITY_CRITICAL = 'critical';

    public const STATUS_PENDING = 'pending';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_DONE = 'done';

    public const STATUS_REJECTED_WITH_REASON = 'rejected_with_reason';

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function response(): HasOne
    {
        return $this->hasOne(ReviewerResponse::class);
    }
}
