<?php

namespace Tests\Feature;

use App\Models\AnalysisResult;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\DocumentVersion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ComparisonApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_compare_two_analyzed_versions(): void
    {
        $user = User::factory()->create();
        [$document, $firstVersion, $secondVersion] = $this->createDocumentWithVersions($user);
        $this->createAnalysis($document, $firstVersion, 70, [
            ['Metode', 60],
            ['Kesimpulan', 80],
        ]);
        $this->createAnalysis($document, $secondVersion, 85, [
            ['Metode', 90],
            ['Kesimpulan', 80],
        ]);
        Sanctum::actingAs($user);

        $this->getJson(
            "/api/documents/{$document->id}/comparison?from_version_id={$firstVersion->id}&to_version_id={$secondVersion->id}",
        )
            ->assertOk()
            ->assertJsonPath('data.total_difference', 15)
            ->assertJsonPath('data.total_status', 'improved')
            ->assertJsonPath('data.aspect_comparison.0.aspect_name', 'Kesimpulan')
            ->assertJsonPath('data.aspect_comparison.0.status', 'unchanged')
            ->assertJsonPath('data.aspect_comparison.1.aspect_name', 'Metode')
            ->assertJsonPath('data.aspect_comparison.1.difference', 30)
            ->assertJsonPath('data.aspect_comparison.1.status', 'improved');
    }

    public function test_comparison_requires_both_versions_to_be_analyzed(): void
    {
        $user = User::factory()->create();
        [$document, $firstVersion, $secondVersion] = $this->createDocumentWithVersions($user);
        $this->createAnalysis($document, $firstVersion, 70, [['Metode', 70]]);
        Sanctum::actingAs($user);

        $this->getJson(
            "/api/documents/{$document->id}/comparison?from_version_id={$firstVersion->id}&to_version_id={$secondVersion->id}",
        )
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Kedua versi harus sudah dianalisis terlebih dahulu.');
    }

    public function test_comparison_rejects_version_from_another_document_and_unauthorized_user(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        [$document, $firstVersion, $secondVersion] = $this->createDocumentWithVersions($owner);
        [$otherDocument, $otherVersion] = $this->createDocumentWithVersions($owner);

        Sanctum::actingAs($owner);
        $this->getJson(
            "/api/documents/{$document->id}/comparison?from_version_id={$firstVersion->id}&to_version_id={$otherVersion->id}",
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors('to_version_id');

        Sanctum::actingAs($otherUser);
        $this->getJson(
            "/api/documents/{$document->id}/comparison?from_version_id={$firstVersion->id}&to_version_id={$secondVersion->id}",
        )->assertForbidden();

        $this->assertNotSame($document->id, $otherDocument->id);
    }

    private function createDocumentWithVersions(User $user): array
    {
        $type = DocumentType::create([
            'name' => fake()->unique()->slug(2),
            'label' => 'Artikel',
            'is_active' => true,
        ]);
        $document = Document::create([
            'user_id' => $user->id,
            'document_type_id' => $type->id,
            'title' => 'Dokumen Comparison',
            'status' => Document::STATUS_REVISED,
        ]);
        $firstVersion = $this->createVersion($document, 1);
        $secondVersion = $this->createVersion($document, 2);
        $document->update(['latest_version_id' => $secondVersion->id]);

        return [$document->fresh(), $firstVersion, $secondVersion];
    }

    private function createVersion(Document $document, int $number): DocumentVersion
    {
        return $document->versions()->create([
            'version_number' => $number,
            'file_path' => "documents/version-{$number}.pdf",
            'file_original_name' => "version-{$number}.pdf",
            'file_type' => 'pdf',
            'file_size' => 100,
            'extracted_text' => "Teks versi {$number}.",
            'uploaded_at' => now(),
        ]);
    }

    private function createAnalysis(
        Document $document,
        DocumentVersion $version,
        int $totalScore,
        array $aspects,
    ): AnalysisResult {
        $analysis = AnalysisResult::create([
            'document_id' => $document->id,
            'document_version_id' => $version->id,
            'total_score' => $totalScore,
            'status' => 'Cukup',
            'summary' => 'Analisis.',
            'main_issues' => [],
            'recommendations' => [],
            'revision_priorities' => [],
            'raw_ai_response' => [],
        ]);

        foreach ($aspects as [$name, $score]) {
            $analysis->aspectScores()->create([
                'aspect_name' => $name,
                'score' => $score,
                'status' => 'Cukup',
                'finding' => 'Temuan.',
                'recommendation' => 'Rekomendasi.',
            ]);
        }

        return $analysis;
    }
}
