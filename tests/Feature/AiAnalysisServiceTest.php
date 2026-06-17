<?php

namespace Tests\Feature;

use App\Exceptions\AiAnalysisException;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\User;
use App\Services\AiAnalysisService;
use App\Services\AiProviderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

class AiAnalysisServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_parses_ai_json_and_calculates_weighted_total_score(): void
    {
        $document = $this->createDocument();
        $this->mock(AiProviderService::class, function (MockInterface $mock): void {
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
        $this->mock(AiProviderService::class, function (MockInterface $mock): void {
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

        $this->mock(AiProviderService::class, function (MockInterface $mock): void {
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

    public function test_it_preserves_reference_score_for_a_numbered_reference_list_without_heading(): void
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
            extractedText: <<<'TEXT'
Isi artikel dan pembahasan penelitian.

[1] Penulis A. Judul artikel pertama. https://doi.org/10.1000/pertama
[2] Penulis B. Judul artikel kedua. https://doi.org/10.1000/kedua
[3] Penulis C. Judul artikel ketiga. https://doi.org/10.1000/ketiga
TEXT,
        );

        $this->mock(AiProviderService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('getContent')
                ->once()
                ->withArgs(fn (array $messages): bool => str_contains(
                    $messages[1]['content'],
                    'Bagian daftar referensi/pustaka: ADA',
                ))
                ->andReturn(<<<'JSON'
{
  "summary": "Referensi tersedia.",
  "main_issues": [],
  "recommendations": [],
  "revision_priorities": [],
  "aspect_scores": [
    {
      "aspect_name": "Referensi",
      "score": 80,
      "finding": "Referensi cukup baik.",
      "recommendation": "Perbarui beberapa sumber."
    }
  ]
}
JSON);
        });

        $result = app(AiAnalysisService::class)->analyze($document);

        $this->assertSame(80, $result['total_score']);
        $this->assertSame(80, $result['aspect_scores'][0]['score']);
    }

    public function test_it_does_not_allow_zero_reference_score_when_a_reference_list_exists(): void
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
            extractedText: <<<'TEXT'
REFERENSI
[1] Penulis A. Judul artikel pertama.
[2] Penulis B. Judul artikel kedua.
[3] Penulis C. Judul artikel ketiga.
TEXT,
        );

        $this->mock(AiProviderService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('getContent')
                ->once()
                ->andReturn(<<<'JSON'
{
  "summary": "Referensi perlu diperbaiki.",
  "main_issues": [],
  "recommendations": [],
  "revision_priorities": [],
  "aspect_scores": [
    {
      "aspect_name": "Referensi",
      "score": 0,
      "finding": "Referensi tidak ada.",
      "recommendation": "Tambahkan referensi."
    }
  ]
}
JSON);
        });

        $result = app(AiAnalysisService::class)->analyze($document);

        $this->assertSame(10, $result['total_score']);
        $this->assertSame(10, $result['aspect_scores'][0]['score']);
        $this->assertSame(
            'Daftar referensi terdeteksi, tetapi kualitas atau kelengkapannya belum dapat dinilai dengan baik.',
            $result['aspect_scores'][0]['finding'],
        );
    }

    public function test_it_includes_the_end_of_a_long_document_in_the_ai_prompt(): void
    {
        config()->set('services.ai.document_character_limit', 12000);

        $document = $this->createDocument(
            extractedText: "PENDAHULUAN\n".str_repeat('isi awal dokumen ', 1500)
                ."\nKESIMPULAN\nTemuan akhir penelitian.\nREFERENSI\n[1] Sumber penelitian.",
        );

        $this->mock(AiProviderService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('getContent')
                ->once()
                ->withArgs(function (array $messages): bool {
                    $prompt = $messages[1]['content'];

                    return str_contains($prompt, 'PENDAHULUAN')
                        && str_contains($prompt, 'KESIMPULAN')
                        && str_contains($prompt, 'REFERENSI')
                        && str_contains($prompt, 'bagian dokumen dipotong karena batas konteks');
                })
                ->andReturn(<<<'JSON'
{
  "summary": "Analisis selesai.",
  "main_issues": [],
  "recommendations": [],
  "revision_priorities": [],
  "aspect_scores": [
    {
      "aspect_name": "Metode",
      "score": 80,
      "finding": "Metode cukup jelas.",
      "recommendation": "Lengkapi prosedur."
    },
    {
      "aspect_name": "Kesimpulan",
      "score": 90,
      "finding": "Kesimpulan tersedia.",
      "recommendation": "Pertahankan."
    }
  ]
}
JSON);
        });

        $result = app(AiAnalysisService::class)->analyze($document);

        $this->assertSame(83, $result['total_score']);
    }

    public function test_it_normalizes_ai_scores_when_all_aspects_use_a_ten_point_scale(): void
    {
        $document = $this->createDocument();
        $this->mock(AiProviderService::class, function (MockInterface $mock): void {
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

    public function test_it_uses_updated_active_database_rubrics_and_ignores_inactive_aspects(): void
    {
        $document = $this->createDocument(rubrics: [
            [
                'aspect_name' => 'Metode',
                'weight' => 50,
                'description' => 'Deskripsi lama dari seed.',
                'is_active' => true,
            ],
            [
                'aspect_name' => 'Referensi',
                'weight' => 50,
                'description' => 'Referensi relevan dan cukup baru.',
                'is_active' => false,
            ],
        ]);

        $document->documentType->rubrics()
            ->where('aspect_name', 'Metode')
            ->first()
            ->update([
                'weight' => 100,
                'description' => 'Deskripsi terbaru dari admin.',
            ]);

        $this->mock(AiProviderService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('getContent')
                ->once()
                ->withArgs(function (array $messages): bool {
                    $rubricSection = str($messages[1]['content'])
                        ->between("Rubrik penilaian:\n", "\n\nFakta struktur dokumen:")
                        ->toString();

                    return str_contains($rubricSection, 'Metode (100%): Deskripsi terbaru dari admin.')
                        && ! str_contains($rubricSection, 'Referensi');
                })
                ->andReturn(<<<'JSON'
{
  "summary": "Analisis mengikuti rubrik aktif.",
  "main_issues": [],
  "recommendations": [],
  "revision_priorities": [],
  "aspect_scores": [
    {
      "aspect_name": "Metode",
      "score": 77,
      "finding": "Metode cukup.",
      "recommendation": "Lengkapi detail metode."
    }
  ]
}
JSON);
        });

        $result = app(AiAnalysisService::class)->analyze($document->fresh());

        $this->assertSame(77, $result['total_score']);
        $this->assertCount(1, $result['aspect_scores']);
        $this->assertSame('Metode', $result['aspect_scores'][0]['aspect_name']);
        $this->assertNotContains('Referensi', array_column($result['aspect_scores'], 'aspect_name'));
    }

    public function test_it_uses_the_database_rubric_for_article_proposal_and_report_documents(): void
    {
        $documents = [
            $this->createDocument(
                rubrics: [[
                    'aspect_name' => 'Struktur Artikel',
                    'weight' => 100,
                    'description' => 'Struktur artikel dari database.',
                    'is_active' => true,
                ]],
                typeName: 'article',
            ),
            $this->createDocument(
                rubrics: [[
                    'aspect_name' => 'Tujuan Proposal',
                    'weight' => 100,
                    'description' => 'Tujuan proposal dari database.',
                    'is_active' => true,
                ]],
                typeName: 'proposal',
            ),
            $this->createDocument(
                rubrics: [[
                    'aspect_name' => 'Evaluasi Laporan',
                    'weight' => 100,
                    'description' => 'Evaluasi laporan dari database.',
                    'is_active' => true,
                ]],
                typeName: 'report',
            ),
        ];

        $expectations = [
            ['article', 'Struktur Artikel', 'Struktur artikel dari database.'],
            ['proposal', 'Tujuan Proposal', 'Tujuan proposal dari database.'],
            ['report', 'Evaluasi Laporan', 'Evaluasi laporan dari database.'],
        ];

        $this->mock(AiProviderService::class, function (MockInterface $mock) use ($expectations): void {
            foreach ($expectations as [$typeName, $aspectName, $description]) {
                $mock->shouldReceive('getContent')
                    ->once()
                    ->ordered()
                    ->withArgs(fn (array $messages): bool => str_contains($messages[1]['content'], "Jenis dokumen: {$typeName}")
                        && str_contains($messages[1]['content'], "{$aspectName} (100%): {$description}"))
                    ->andReturn(<<<JSON
{
  "summary": "Analisis {$typeName}.",
  "main_issues": [],
  "recommendations": [],
  "revision_priorities": [],
  "aspect_scores": [
    {
      "aspect_name": "{$aspectName}",
      "score": 80,
      "finding": "Aspek sesuai.",
      "recommendation": "Pertahankan."
    }
  ]
}
JSON);
            }
        });

        foreach ($documents as $index => $document) {
            $result = app(AiAnalysisService::class)->analyze($document);

            $this->assertSame($expectations[$index][1], $result['aspect_scores'][0]['aspect_name']);
            $this->assertSame(80, $result['total_score']);
        }
    }

    private function createDocument(
        ?array $rubrics = null,
        string $extractedText = 'Isi dokumen akademik.',
        ?string $typeName = null,
    ): Document {
        $user = User::factory()->create();
        $type = DocumentType::create([
            'name' => $typeName ?? fake()->unique()->slug(2),
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
