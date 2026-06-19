<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JournalRecommendation extends Model
{
    protected $fillable = [
        'document_id',
        'journal_id',
        'fit_score',
        'fit_reason',
        'submission_risk',
        'suggested_improvement',
        'raw_ai_response',
    ];

    protected function casts(): array
    {
        return [
            'raw_ai_response' => 'array',
        ];
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class);
    }
}
