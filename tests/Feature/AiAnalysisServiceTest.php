<?php

namespace Tests\Feature;

use App\Exceptions\AiAnalysisException;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\User;
use App\Services\AiAnalysisService;
use App\Services\GroqService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

class AiAnalysisServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_parses_ai_json_and_calculates_weighted_total_score(): void
    {
        $document = $this->createDocument();
        $this->mock(GroqService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('getContent')
                ->once()
                ->withArgs(function (array $messages): bool {
                    return str_contains($messages[1]['content'], 'Metode (75%)')
                        && str_contains($messages[1]['content'], '<document>');
                })
                ->andReturn(<<<'JSON'
```json
{
  "summary": "Analisis selesai.",
  "main_issues": ["Metode perlu diperjelas."],
  "recommendations": ["Tambahkan prosedur."],
  "revision_priorities": ["Metode"],
  "aspect_scores": [
    {
      "aspect_name": "Metode",
      "score": 80,
      "finding": "Metode cukup jelas.",
      "recommendation": "Tambahkan prosedur."
    },
    {
      "aspect_name": "Kesimpulan",
      "score": 100,
      "finding": "Kesimpulan sangat baik.",
      "recommendation": "Pertahankan."
    }
  ]
}
```
JSON);
        });

        $result = app(AiAnalysisService::class)->analyze($document);

        $this->assertSame(85, $result['total_score']);
        $this->assertSame('Baik', $result['status']);
        $this->assertSame('Cukup', $result['aspect_scores'][0]['status']);
        $this->assertSame('Baik', $result['aspect_scores'][1]['status']);
    }

    public function test_it_rejects_invalid_ai_json(): void
    {
        $document = $this->createDocument();
        $this->mock(GroqService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('getContent')->once()->andReturn('not-json');
        });

        $this->expectException(AiAnalysisException::class);
        $this->expectExceptionMessage('Output layanan AI bukan JSON yang valid.');

        app(AiAnalysisService::class)->analyze($document);
    }

    public function test_it_forces_reference_score_to_zero_when_reference_section_is_missing(): void
    {
        $document = $this->createDocument(
            rubrics: [
                [
                    'aspect_name' => 'Referensi',
                    'weight' => 100,
                    'description' => 'Referensi relevan dan cukup baru.',
                    'is_active' => true,
                ],
            ],
            extractedText: 'Artikel ini menggunakan referensi ilmiah, tetapi tidak memiliki bagian daftar pustaka.',
        );

        $this->mock(GroqService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('getContent')
                ->once()
                ->withArgs(fn (array $messages): bool => str_contains(
                    $messages[1]['content'],
                    'Bagian daftar referensi/pustaka: TIDAK ADA',
                ))
                ->andReturn(<<<'JSON'
{
  "summary": "Referensi dinilai baik.",
  "main_issues": [],
  "recommendations": [],
  "revision_priorities": [],
  "aspect_scores": [
    {
      "aspect_name": "Referensi",
      "score": 95,
      "finding": "Referensi lengkap.",
      "recommendation": "Pertahankan."
    }
  ]
}
JSON);
        });

        $result = app(AiAnalysisService::class)->analyze($document);

        $this->assertSame(0, $result['total_score']);
        $this->assertSame(0, $result['aspect_scores'][0]['score']);
        $this->assertSame('Revisi Besar', $result['aspect_scores'][0]['status']);
        $this->assertSame(
            'Dokumen tidak memiliki bagian daftar referensi atau daftar pustaka.',
            $result['aspect_scores'][0]['finding'],
        );
        $this->assertSame(
            'Dokumen belum memiliki bagian daftar referensi atau daftar pustaka.',
            $result['main_issues'][0],
        );
        $this->assertSame(
            'Tambahkan dan lengkapi daftar referensi.',
            $result['revision_priorities'][0],
        );
    }

    public function test_it_normalizes_ai_scores_when_all_aspects_use_a_ten_point_scale(): void
    {
        $document = $this->createDocument();
        $this->mock(GroqService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('getContent')->once()->andReturn(<<<'JSON'
{
  "summary": "Analisis selesai.",
  "main_issues": [],
  "recommendations": [],
  "revision_priorities": [],
  "aspect_scores": [
    {
      "aspect_name": "Metode",
      "score": 8,
      "finding": "Metode cukup jelas.",
      "recommendation": "Lengkapi prosedur."
    },
    {
      "aspect_name": "Kesimpulan",
      "score": 9,
      "finding": "Kesimpulan sangat baik.",
      "recommendation": "Pertahankan."
    }
  ]
}
JSON);
        });

        $result = app(AiAnalysisService::class)->analyze($document);

        $this->assertSame(83, $result['total_score']);
        $this->assertSame(80, $result['aspect_scores'][0]['score']);
        $this->assertSame('Cukup', $result['aspect_scores'][0]['status']);
        $this->assertSame(90, $result['aspect_scores'][1]['score']);
        $this->assertSame('Baik', $result['aspect_scores'][1]['status']);
    }

    private function createDocument(?array $rubrics = null, string $extractedText = 'Isi dokumen akademik.'): Document
    {
        $user = User::factory()->create();
        $type = DocumentType::create([
            'name' => fake()->unique()->slug(2),
            'label' => 'Artikel',
            'is_active' => true,
        ]);
        $type->rubrics()->createMany($rubrics ?? [
            [
                'aspect_name' => 'Metode',
                'weight' => 75,
                'description' => 'Metode harus jelas.',
                'is_active' => true,
            ],
            [
                'aspect_name' => 'Kesimpulan',
                'weight' => 25,
                'description' => 'Kesimpulan menjawab tujuan.',
                'is_active' => true,
            ],
        ]);

        $document = Document::create([
            'user_id' => $user->id,
            'document_type_id' => $type->id,
            'title' => 'Dokumen AI',
            'status' => Document::STATUS_UPLOADED,
        ]);
        $version = $document->versions()->create([
            'version_number' => 1,
            'file_path' => 'documents/sample.pdf',
            'file_original_name' => 'sample.pdf',
            'file_type' => 'pdf',
            'file_size' => 100,
            'extracted_text' => $extractedText,
            'uploaded_at' => now(),
        ]);
        $document->update(['latest_version_id' => $version->id]);

        return $document->fresh();
    }
}
