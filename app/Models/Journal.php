<?php

namespace App\Models;

use App\Services\JournalEligibilityService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Journal extends Model
{
    protected $fillable = [
        'name',
        'publisher',
        'sinta_level',
        'subject_area',
        'focus_scope',
        'keywords',
        'p_issn',
        'e_issn',
        'website_url',
        'editor_url',
        'template_url',
        'author_guideline_url',
        'indexing',
        'impact',
        'h5_index',
        'citations_5yr',
        'citations_total',
        'source_url',
        'raw_text',
        'is_active',
        'verification_status',
        'eligibility_score',
        'last_verified_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'eligibility_score' => 'integer',
        'last_verified_at' => 'date',
    ];

    protected static function booted(): void
    {
        static::saving(function (Journal $journal): void {
            $journal->eligibility_score = app(JournalEligibilityService::class)
                ->calculate($journal->getAttributes());
        });
    }

    public function recommendations(): HasMany
    {
        return $this->hasMany(JournalRecommendation::class);
    }
}
