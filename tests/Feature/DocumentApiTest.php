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

class DocumentApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_upload_document_to_private_storage(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $type = $this->createDocumentType();
        Sanctum::actingAs($user);
        $this->mock(TextExtractionService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('extract')
                ->once()
                ->andReturn('Teks artikel yang berhasil diekstrak.');
        });

        $response = $this->post('/api/documents', [
            'document_type_id' => $type->id,
            'title' => 'Penelitian Sistem Informasi',
            'topic' => 'Sistem Informasi',
            'keywords' => 'laravel, akademik',
            'file' => UploadedFile::fake()->create('artikel.pdf', 100, 'application/pdf'),
        ], ['Accept' => 'application/json']);

        $response
            ->assertCreated()
            ->assertJsonPath('message', 'Dokumen berhasil diunggah dan teks berhasil diekstrak.')
            ->assertJsonPath('data.document.user_id', $user->id)
            ->assertJsonPath('data.version.version_number', 1)
            ->assertJsonPath('data.extracted_text_preview', 'Teks artikel yang berhasil diekstrak.');

        $document = Document::firstOrFail();
        $version = $document->versions()->firstOrFail();

        Storage::disk('local')->assertExists($version->file_path);
        $this->assertSame($version->id, $document->latest_version_id);
    }

    public function test_authenticated_user_can_upload_docx_document(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $type = $this->createDocumentType();
        Sanctum::actingAs($user);
        $this->mock(TextExtractionService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('extract')
                ->once()
                ->andReturn('Teks DOCX yang berhasil diekstrak.');
        });

        $this->post('/api/documents', [
            'document_type_id' => $type->id,
            'title' => 'Proposal Penelitian',
            'file' => UploadedFile::fake()->create(
                'proposal.docx',
                100,
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ),
        ], ['Accept' => 'application/json'])
            ->assertCreated()
            ->assertJsonPath('data.version.file_type', 'docx')
            ->assertJsonPath('data.extracted_text_preview', 'Teks DOCX yang berhasil diekstrak.');

        Storage::disk('local')->assertExists(Document::firstOrFail()->latestVersion->file_path);
    }

    public function test_document_library_only_lists_the_authenticated_users_documents(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $type = $this->createDocumentType();

        $ownedDocument = $this->createDocument($user, $type, 'Dokumen Saya');
        $this->createDocument($otherUser, $type, 'Dokumen Orang Lain');

        Sanctum::actingAs($user);

        $this->getJson('/api/documents')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $ownedDocument->id)
            ->assertJsonPath('data.0.document_type.id', $type->id)
            ->assertJsonPath('data.0.latest_version.version_number', 1)
            ->assertJsonMissing(['title' => 'Dokumen Orang Lain']);
    }

    public function test_user_cannot_view_another_users_document_but_admin_can(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $document = $this->createDocument($owner, $this->createDocumentType(), 'Dokumen Privat');

        Sanctum::actingAs($otherUser);
        $this->getJson("/api/documents/{$document->id}")
            ->assertForbidden()
            ->assertJsonPath('message', 'Anda tidak memiliki akses ke dokumen ini.');

        Sanctum::actingAs($admin);
        $this->getJson("/api/documents/{$document->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $document->id)
            ->assertJsonPath('data.document_type.id', $document->document_type_id)
            ->assertJsonPath('data.versions.0.version_number', 1)
            ->assertJsonPath('data.latest_version.version_number', 1);
    }

    public function test_upload_requires_an_active_document_type_and_supported_file(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $inactiveType = $this->createDocumentType(['is_active' => false]);
        Sanctum::actingAs($user);

        $this->post('/api/documents', [
            'document_type_id' => $inactiveType->id,
            'title' => 'Dokumen Tidak Valid',
            'file' => UploadedFile::fake()->create('catatan.txt', 10, 'text/plain'),
        ], ['Accept' => 'application/json'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['document_type_id', 'file']);

        $this->assertDatabaseCount('documents', 0);
    }

    public function test_failed_text_extraction_rolls_back_document_and_file(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $type = $this->createDocumentType();
        Sanctum::actingAs($user);
        $this->mock(TextExtractionService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('extract')
                ->once()
                ->andThrow(new TextExtractionException('Dokumen tidak dapat dibaca.'));
        });

        $this->post('/api/documents', [
            'document_type_id' => $type->id,
            'title' => 'Dokumen Rusak',
            'file' => UploadedFile::fake()->create('rusak.pdf', 10, 'application/pdf'),
        ], ['Accept' => 'application/json'])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Dokumen tidak dapat dibaca.');

        $this->assertDatabaseCount('documents', 0);
        $this->assertDatabaseCount('document_versions', 0);
        Storage::disk('local')->assertDirectoryEmpty('');
    }

    private function createDocumentType(array $attributes = []): DocumentType
    {
        return DocumentType::create(array_merge([
            'name' => fake()->unique()->slug(2),
            'label' => fake()->words(2, true),
            'description' => fake()->sentence(),
            'is_active' => true,
        ], $attributes));
    }

    private function createDocument(User $user, DocumentType $type, string $title): Document
    {
        $document = Document::create([
            'user_id' => $user->id,
            'document_type_id' => $type->id,
            'title' => $title,
            'status' => Document::STATUS_UPLOADED,
        ]);

        $version = $document->versions()->create([
            'version_number' => 1,
            'file_path' => "documents/user_{$user->id}/document_{$document->id}/sample.pdf",
            'file_original_name' => 'sample.pdf',
            'file_type' => 'pdf',
            'file_size' => 100,
            'uploaded_at' => now(),
        ]);

        $document->update(['latest_version_id' => $version->id]);

        return $document;
    }
}
