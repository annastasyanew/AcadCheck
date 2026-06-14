<?php

namespace Tests\Feature;

use App\Exceptions\AiProviderException;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\Rubric;
use App\Models\User;
use App\Services\AiAnalysisService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Mockery\MockInterface;
use Tests\TestCase;

class AnalysisApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_run_ai_analysis_and_view_latest_result(): void
    {
        $user = User::factory()->create();
        [$document, $rubrics] = $this->createAnalyzableDocument($user);
        Sanctum::actingAs($user);
        $this->mock(AiAnalysisService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('analyze')
                ->once()
                ->andReturn($this->aiResult());
        });

        $this->postJson("/api/documents/{$document->id}/analyze")
            ->assertCreated()
            ->assertJsonPath('message', 'Analisis AI berhasil.')
            ->assertJsonPath('data.total_score', 88)
            ->assertJsonCount($rubrics->count(), 'data.aspect_scores');

        $this->assertDatabaseHas('documents', [
            'id' => $document->id,
            'status' => Document::STATUS_READY,
            'latest_score' => 88,
        ]);
        $this->assertDatabaseCount('analysis_results', 1);
        $this->assertDatabaseCount('analysis_aspect_scores', $rubrics->count());

        $this->getJson("/api/documents/{$document->id}/analysis")
            ->assertOk()
            ->assertJsonPath('data.total_score', 88)
            ->assertJsonCount($rubrics->count(), 'data.aspect_scores');
    }

    public function test_document_without_extracted_text_cannot_be_analyzed(): void
    {
        $user = User::factory()->create();
        [$document] = $this->createAnalyzableDocument($user, null);
        Sanctum::actingAs($user);

        $this->postJson("/api/documents/{$document->id}/analyze")
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Teks dokumen belum tersedia untuk dianalisis.');

        $this->assertDatabaseCount('analysis_results', 0);
    }

    public function test_other_user_cannot_analyze_or_view_analysis(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        [$document] = $this->createAnalyzableDocument($owner);
        Sanctum::actingAs($otherUser);

        $this->postJson("/api/documents/{$document->id}/analyze")
            ->assertForbidden()
            ->assertJsonPath('message', 'Tidak memiliki akses.');

        $this->getJson("/api/documents/{$document->id}/analysis")
            ->assertForbidden()
            ->assertJsonPath('message', 'Tidak memiliki akses.');
    }

    public function test_ai_service_failure_does_not_store_partial_analysis_or_expose_error(): void
    {
        $user = User::factory()->create();
        [$document] = $this->createAnalyzableDocument($user);
        Sanctum::actingAs($user);
        $this->mock(AiAnalysisService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('analyze')
                ->once()
                ->andThrow(new AiProviderException('Secret upstream response.'));
        });

        $this->postJson("/api/documents/{$document->id}/analyze")
            ->assertStatus(502)
            ->assertJsonPath('message', 'Analisis AI gagal. Silakan coba kembali.')
            ->assertJsonMissing(['error' => 'Secret upstream response.']);

        $this->assertDatabaseCount('analysis_results', 0);
        $this->assertDatabaseCount('analysis_aspect_scores', 0);
    }

    public function test_reanalysis_uses_the_latest_document_version(): void
    {
        $user = User::factory()->create();
        [$document] = $this->createAnalyzableDocument($user);
        $secondVersion = $document->versions()->create([
            'version_number' => 2,
            'file_path' => 'document-revisions/sample-v2.pdf',
            'file_original_name' => 'sample-v2.pdf',
            'file_type' => 'pdf',
            'file_size' => 100,
            'extracted_text' => 'Isi versi revisi.',
            'uploaded_at' => now(),
        ]);
        $document->update([
            'latest_version_id' => $secondVersion->id,
            'status' => Document::STATUS_REVISED,
        ]);
        Sanctum::actingAs($user);
        $this->mock(AiAnalysisService::class, function (MockInterface $mock) use ($secondVersion): void {
            $mock->shouldReceive('analyze')
                ->once()
                ->withArgs(fn (Document $document): bool => $document->latestVersion->id === $secondVersion->id)
                ->andReturn($this->aiResult());
        });

        $this->postJson("/api/documents/{$document->id}/analyze")->assertCreated();

        $this->assertDatabaseHas('analysis_results', [
            'document_id' => $document->id,
            'document_version_id' => $secondVersion->id,
        ]);
    }

    private function createAnalyzableDocument(User $user, ?string $text = 'Isi dokumen akademik.'): array
    {
        $type = DocumentType::create([
            'name' => fake()->unique()->slug(2),
            'label' => 'Artikel Ilmiah',
            'is_active' => true,
        ]);

        $rubrics = collect([
            Rubric::create([
                'document_type_id' => $type->id,
                'aspect_name' => 'Metode',
                'weight' => 50,
                'is_active' => true,
            ]),
            Rubric::create([
                'document_type_id' => $type->id,
                'aspect_name' => 'Kesimpulan',
                'weight' => 50,
                'is_active' => true,
            ]),
        ]);

        $document = Document::create([
            'user_id' => $user->id,
            'document_type_id' => $type->id,
            'title' => 'Dokumen Analisis',
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

        return [$document->fresh(), $rubrics];
    }

    private function aiResult(): array
    {
        return [
            'total_score' => 88,
            'status' => 'Baik',
            'summary' => 'Dokumen sudah baik.',
            'main_issues' => ['Metode perlu sedikit diperjelas.'],
            'recommendations' => ['Tambahkan rincian metode.'],
            'revision_priorities' => ['Metode'],
            'aspect_scores' => [
                [
                    'aspect_name' => 'Metode',
                    'score' => 90,
                    'status' => 'Baik',
                    'finding' => 'Metode cukup jelas.',
                    'recommendation' => 'Tambahkan detail.',
                ],
                [
                    'aspect_name' => 'Kesimpulan',
                    'score' => 86,
                    'status' => 'Baik',
                    'finding' => 'Kesimpulan menjawab tujuan.',
                    'recommendation' => 'Pertahankan.',
                ],
            ],
        ];
    }
}
