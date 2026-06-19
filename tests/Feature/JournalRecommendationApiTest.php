<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\DocumentType;
use App\Models\Journal;
use App\Models\User;
use App\Services\AiProviderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Mockery\MockInterface;
use Tests\TestCase;

class JournalRecommendationApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_generate_and_view_journal_recommendations_from_eligible_journals(): void
    {
        $user = User::factory()->create();
        $document = $this->createDocument($user, 'article', 'Artikel Ilmiah');
        [$firstJournal, $secondJournal, $thirdJournal] = $this->createActiveVerifiedJournals();
        $lowQualityJournal = Journal::create([
            'name' => 'Low Quality Metadata Journal',
            'sinta_level' => 'S2',
            'subject_area' => 'Computer Science',
            'is_active' => true,
            'verification_status' => 'verified',
        ]);
        $inactiveJournal = Journal::create([
            'name' => 'Inactive Journal',
            'subject_area' => 'Computer Science',
            'focus_scope' => 'AI research.',
            'is_active' => false,
            'verification_status' => 'verified',
        ]);
        Sanctum::actingAs($user);
        $this->mock(AiProviderService::class, function (MockInterface $mock) use (
            $firstJournal,
            $secondJournal,
            $thirdJournal,
            $lowQualityJournal,
            $inactiveJournal,
        ): void {
            $mock->shouldReceive('getContent')
                ->once()
                ->andReturn(json_encode([
                    'recommendations' => [
                        [
                            'journal_id' => $firstJournal->id,
                            'fit_score' => 91,
                            'fit_reason' => 'Scope AI sesuai.',
                            'submission_risk' => 'Perlu cek template.',
                            'suggested_improvement' => 'Perjelas kontribusi.',
                        ],
                        [
                            'journal_id' => $inactiveJournal->id,
                            'fit_score' => 99,
                            'fit_reason' => 'Tidak boleh tersimpan.',
                        ],
                        [
                            'journal_id' => $lowQualityJournal->id,
                            'fit_score' => 97,
                            'fit_reason' => 'Skor metadata rendah sehingga tidak boleh tersimpan.',
                        ],
                        [
                            'journal_id' => $secondJournal->id,
                            'fit_score' => 82,
                            'fit_reason' => 'Subject area cocok.',
                        ],
                        [
                            'journal_id' => $thirdJournal->id,
                            'fit_score' => 73,
                            'fit_reason' => 'Masih relevan.',
                        ],
                    ],
                ], JSON_UNESCAPED_UNICODE));
        });

        $this->postJson("/api/documents/{$document->id}/journal-recommendations")
            ->assertCreated()
            ->assertJsonPath('message', 'Rekomendasi jurnal berhasil dibuat.')
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('data.0.journal_id', $firstJournal->id)
            ->assertJsonPath('data.0.fit_score', 91)
            ->assertJsonPath('data.0.journal.id', $firstJournal->id);

        $this->assertDatabaseCount('journal_recommendations', 3);
        $this->assertDatabaseMissing('journal_recommendations', [
            'journal_id' => $inactiveJournal->id,
        ]);
        $this->assertDatabaseMissing('journal_recommendations', [
            'journal_id' => $lowQualityJournal->id,
        ]);

        $this->getJson("/api/documents/{$document->id}/journal-recommendations")
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('data.0.fit_score', 91);
    }

    public function test_recommendation_requires_article_document_with_extracted_text(): void
    {
        $user = User::factory()->create();
        $proposal = $this->createDocument($user, 'proposal', 'Proposal');
        $articleWithoutText = $this->createDocument($user, 'article', 'Artikel Ilmiah', null);
        $this->createActiveVerifiedJournals();
        Sanctum::actingAs($user);

        $this->postJson("/api/documents/{$proposal->id}/journal-recommendations")
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Rekomendasi jurnal hanya tersedia untuk artikel ilmiah.');

        $this->postJson("/api/documents/{$articleWithoutText->id}/journal-recommendations")
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Teks artikel belum tersedia untuk dianalisis.');
    }

    public function test_other_user_cannot_access_journal_recommendations(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $document = $this->createDocument($owner, 'article', 'Artikel Ilmiah');
        Sanctum::actingAs($otherUser);

        $this->getJson("/api/documents/{$document->id}/journal-recommendations")
            ->assertForbidden()
            ->assertJsonPath('message', 'Tidak memiliki akses.');

        $this->postJson("/api/documents/{$document->id}/journal-recommendations")
            ->assertForbidden()
            ->assertJsonPath('message', 'Tidak memiliki akses.');
    }

    public function test_generation_requires_at_least_three_active_verified_journals(): void
    {
        $user = User::factory()->create();
        $document = $this->createDocument($user, 'article', 'Artikel Ilmiah');
        Journal::create([
            'name' => 'Only Journal',
            'subject_area' => 'Computer Science',
            'focus_scope' => 'AI research.',
            'is_active' => true,
            'verification_status' => 'verified',
        ]);
        Sanctum::actingAs($user);

        $this->postJson("/api/documents/{$document->id}/journal-recommendations")
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Data jurnal eligible AI belum cukup. Lengkapi metadata minimal 3 jurnal dengan eligibility score 70+.');
    }

    private function createDocument(
        User $user,
        string $typeName,
        string $typeLabel,
        ?string $text = 'Artikel membahas deep learning untuk klasifikasi citra medis.',
    ): Document {
        $type = DocumentType::create([
            'name' => $typeName,
            'label' => $typeLabel,
            'is_active' => true,
        ]);

        $document = Document::create([
            'user_id' => $user->id,
            'document_type_id' => $type->id,
            'title' => 'Knowledge Distillation untuk Klasifikasi Citra Medis',
            'topic' => 'Artificial Intelligence',
            'keywords' => 'deep learning, medical imaging',
            'status' => Document::STATUS_UPLOADED,
        ]);

        $version = $document->versions()->create([
            'version_number' => 1,
            'file_path' => "documents/user_{$user->id}/document_{$document->id}/sample.pdf",
            'file_original_name' => 'sample.pdf',
            'file_type' => 'pdf',
            'file_size' => 100,
            'extracted_text' => $text,
            'uploaded_at' => now(),
        ]);

        $document->update(['latest_version_id' => $version->id]);

        return $document->fresh();
    }

    /**
     * @return array<int, Journal>
     */
    private function createActiveVerifiedJournals(): array
    {
        return [
            Journal::create([
                'name' => 'AI Engineering Journal',
                'sinta_level' => 'S1',
                'subject_area' => 'Computer Science',
                'focus_scope' => 'Artificial intelligence and engineering.',
                'keywords' => 'artificial intelligence; engineering',
                'website_url' => 'https://example.com/ai-engineering',
                'is_active' => true,
                'verification_status' => 'verified',
            ]),
            Journal::create([
                'name' => 'Medical Image Computing Journal',
                'sinta_level' => 'S2',
                'subject_area' => 'Health, Computer Science',
                'focus_scope' => 'Medical imaging and decision support.',
                'keywords' => 'medical imaging; deep learning',
                'website_url' => 'https://example.com/medical-image',
                'is_active' => true,
                'verification_status' => 'verified',
            ]),
            Journal::create([
                'name' => 'Applied Informatics Review',
                'sinta_level' => 'S3',
                'subject_area' => 'Informatics',
                'focus_scope' => 'Applied informatics research.',
                'keywords' => 'informatics; machine learning',
                'website_url' => 'https://example.com/applied-informatics',
                'is_active' => true,
                'verification_status' => 'verified',
            ]),
        ];
    }
}
