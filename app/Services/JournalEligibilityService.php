<?php

namespace App\Services;

class JournalEligibilityService
{
    public const MINIMUM_AI_SCORE = 70;

    private const INVALID_VALUES = ['nan', 'null', 'undefined', '-', '#'];

    public function calculate(array $journal): int
    {
        $score = 0;

        if ($this->hasMeaningfulValue($journal['sinta_level'] ?? null)) {
            $score += 10;
        }

        if ($this->hasMeaningfulValue($journal['subject_area'] ?? null)) {
            $score += 15;
        }

        if ($this->hasMeaningfulValue($journal['keywords'] ?? null)) {
            $score += 20;
        }

        if ($this->hasMeaningfulValue($journal['focus_scope'] ?? null)) {
            $score += 30;
        }

        if ($this->hasValidUrl($journal['website_url'] ?? null)) {
            $score += 10;
        }

        if ($this->hasValidUrl($journal['template_url'] ?? null)) {
            $score += 10;
        }

        if ($this->hasValidUrl($journal['author_guideline_url'] ?? null)) {
            $score += 5;
        }

        return min($score, 100);
    }

    public function isEligibleForAi(array $journal): bool
    {
        return ($journal['eligibility_score'] ?? $this->calculate($journal)) >= self::MINIMUM_AI_SCORE;
    }

    public function hasMeaningfulValue(mixed $value): bool
    {
        $normalizedValue = strtolower(trim((string) ($value ?? '')));

        return $normalizedValue !== ''
            && ! in_array($normalizedValue, self::INVALID_VALUES, true);
    }

    public function hasValidUrl(mixed $value): bool
    {
        if (! $this->hasMeaningfulValue($value)) {
            return false;
        }

        return filter_var(trim((string) $value), FILTER_VALIDATE_URL) !== false
            && preg_match('/^https?:\/\//i', trim((string) $value)) === 1;
    }
}
