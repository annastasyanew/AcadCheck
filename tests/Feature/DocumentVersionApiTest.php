<?php

namespace Tests\Feature;

use App\Exceptions\TextExtractionException;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\User;
use App\Services\TextExtractionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Mockery\MockInterface;
use Tests\TestCase;

class DocumentVersionApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_upload_revision_and_view_version_history(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        [$document, $firstVersion] = $this->createDocument($user);
        $document->update([
            'status' => Document::STATUS_READY,
            'latest_score' => 90,
        ]);
        Sanctum::actingAs($user);
        $this->mock(TextExtractionService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('extract')
                ->once()
                ->andReturn('Teks versi revisi.');
        });

        $response = $this->post('/api/documents/'.$document->id.'/versions', [
            'file' => UploadedFile::fake()->create('revisi.pdf', 100, 'application/pdf'),
            'revision_note' => 'Memperjelas bagian metode.',
        ], ['Accept' => 'application/json']);

        $response
            ->assertCreated()
            ->assertJsonPath('message', 'Versi revisi berhasil diunggah.')
            ->assertJsonPath('data.version.version_number', 2)
            ->assertJsonPath('data.extracted_text_preview', 'Teks versi revisi.');

        $document->refresh();
        $secondVersion = $document->latestVersion;

        $this->assertSame(2, $secondVersion->version_number);
        $this->assertSame('Memperjelas bagian metode.', $secondVersion->revision_note);
        $this->assertSame(Document::STATUS_REVISED, $document->status);
        $this->assertNull($document->latest_score);
        $this->assertNotSame($firstVersion->id, $secondVersion->id);
        Storage::disk('local')->assertExists($secondVersion->file_path);

        $this->getJson("/api/documents/{$document->id}/versions")
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.version_number', 1)
            ->assertJsonPath('data.1.version_number', 2);
    }

    public function test_owner_can_upload_docx_revision(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        [$document] = $this->createDocument($user);
        Sanctum::actingAs($user);
        $this->mock(TextExtractionService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('extract')
                ->once()
                ->andReturn('Teks revisi DOCX.');
        });

        $this->post('/api/documents/'.$document->id.'/versions', [
            'file' => UploadedFile::fake()->create(
                'revisi.docx',
                100,
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ),
        ], ['Accept' => 'application/json'])
            ->assertCreated()
            ->assertJsonPath('data.version.version_number', 2)
            ->assertJsonPath('data.version.file_type', 'docx');

        $this->assertSame(2, $document->fresh()->latestVersion->version_number);
    }

    public function test_only_owner_can_upload_revision_but_admin_can_view_history(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        [$document] = $this->createDocument($owner);

        Sanctum::actingAs($otherUser);
        $this->post('/api/documents/'.$document->id.'/versions', [
            'file' => UploadedFile::fake()->create('revisi.pdf', 100, 'application/pdf'),
        ], ['Accept' => 'application/json'])->assertForbidden();

        Sanctum::actingAs($admin);
        $this->getJson("/api/documents/{$document->id}/versions")
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_failed_revision_extraction_rolls_back_version_and_file(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        [$document, $firstVersion] = $this->createDocument($user);
        Sanctum::actingAs($user);
        $this->mock(TextExtractionService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('extract')
                ->once()
                ->andThrow(new TextExtractionException('Dokumen revisi tidak dapat dibaca.'));
        });

        $this->post('/api/documents/'.$document->id.'/versions', [
            'file' => UploadedFile::fake()->create('rusak.pdf', 10, 'application/pdf'),
        ], ['Accept' => 'application/json'])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Dokumen revisi tidak dapat dibaca.');

        $this->assertDatabaseCount('document_versions', 1);
        $this->assertSame($firstVersion->id, $document->fresh()->latest_version_id);
        Storage::disk('local')->assertDirectoryEmpty('');
    }

    private function createDocument(User $user): array
    {
        $type = DocumentType::create([
            'name' => fake()->unique()->slug(2),
            'label' => 'Artikel',
            'is_active' => true,
        ]);
        $document = Document::create([
            'user_id' => $user->id,
            'document_type_id' => $type->id,
            'title' => 'Dokumen Revisi',
            'status' => Document::STATUS_UPLOADED,
        ]);
        $version = $document->versions()->create([
            'version_number' => 1,
            'file_path' => 'documents/sample.pdf',
            'file_original_name' => 'sample.pdf',
            'file_type' => 'pdf',
            'file_size' => 100,
            'extracted_text' => 'Teks versi pertama.',
            'uploaded_at' => now(),
        ]);
        $document->update(['latest_version_id' => $version->id]);

        return [$document->fresh(), $version];
    }
}
