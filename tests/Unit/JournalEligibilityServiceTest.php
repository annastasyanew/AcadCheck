<?php

namespace Tests\Unit;

use App\Services\JournalEligibilityService;
use PHPUnit\Framework\TestCase;

class JournalEligibilityServiceTest extends TestCase
{
    public function test_complete_journal_metadata_scores_one_hundred(): void
    {
        $service = new JournalEligibilityService();

        $score = $service->calculate([
            'sinta_level' => 'S1',
            'subject_area' => 'Informatika',
            'keywords' => 'artificial intelligence; data mining',
            'focus_scope' => 'Jurnal bidang informatika dan kecerdasan buatan.',
            'website_url' => 'https://example.com',
            'template_url' => 'https://example.com/template',
            'author_guideline_url' => 'https://example.com/guideline',
        ]);

        $this->assertSame(100, $score);
    }

    public function test_sparse_journal_metadata_scores_thirty_five(): void
    {
        $service = new JournalEligibilityService();

        $score = $service->calculate([
            'sinta_level' => 'S2',
            'subject_area' => 'Sistem Informasi',
            'keywords' => null,
            'focus_scope' => null,
            'website_url' => 'https://example.com',
            'template_url' => null,
            'author_guideline_url' => null,
        ]);

        $this->assertSame(35, $score);
    }
}
