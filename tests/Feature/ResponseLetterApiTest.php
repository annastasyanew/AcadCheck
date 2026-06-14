<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\DocumentType;
use App\Models\ReviewerComment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ResponseLetterApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_export_response_letter_pdf_to_private_storage(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $article = $this->createDocument($user, 'article');
        $comment = $article->reviewerComments()->create($this->commentAttributes());
        $comment->response()->create([
            'author_response' => 'Thank you for the valuable comment.',
            'revision_made' => 'Added methodology details.',
            'revision_location' => 'Section 3.2',
        ]);
        Sanctum::actingAs($user);

        $response = $this->get("/api/articles/{$article->id}/response-letter", [
            'Accept' => 'application/pdf',
        ]);

        $fileName = "response_to_reviewers_document_{$article->id}.pdf";
        $filePath = "response-letters/user_{$user->id}/document_{$article->id}/{$fileName}";

        $response
            ->assertOk()
            ->assertDownload($fileName)
            ->assertHeader('content-type', 'application/pdf');
        Storage::disk('local')->assertExists($filePath);

        $pdfContent = Storage::disk('local')->get($filePath);
        $this->assertStringStartsWith('%PDF-', $pdfContent);
        $this->assertGreaterThan(1000, strlen($pdfContent));
    }

    public function test_export_replaces_existing_response_letter_file(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $article = $this->createDocument($user, 'article');
        $article->reviewerComments()->create($this->commentAttributes());
        Sanctum::actingAs($user);

        $this->get("/api/articles/{$article->id}/response-letter")->assertOk();
        $this->get("/api/articles/{$article->id}/response-letter")->assertOk();

        $directory = "response-letters/user_{$user->id}/document_{$article->id}";
        $this->assertCount(1, Storage::disk('local')->files($directory));
    }

    public function test_response_letter_is_article_only_and_requires_comments(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $proposal = $this->createDocument($user, 'proposal');
        $article = $this->createDocument($user, 'article');
        Sanctum::actingAs($user);

        $this->getJson("/api/articles/{$proposal->id}/response-letter")
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Response to Reviewers hanya tersedia untuk artikel ilmiah.');

        $this->getJson("/api/articles/{$article->id}/response-letter")
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Belum ada komentar reviewer untuk artikel ini.');
    }

    public function test_other_user_cannot_export_but_admin_can(): void
    {
        Storage::fake('local');
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $article = $this->createDocument($owner, 'article');
        $article->reviewerComments()->create($this->commentAttributes());

        Sanctum::actingAs($otherUser);
        $this->getJson("/api/articles/{$article->id}/response-letter")->assertForbidden();

        Sanctum::actingAs($admin);
        $this->get("/api/articles/{$article->id}/response-letter")
            ->assertOk()
            ->assertDownload("response_to_reviewers_document_{$article->id}.pdf");
    }

    private function createDocument(User $user, string $typeName): Document
    {
        $type = DocumentType::firstOrCreate([
            'name' => $typeName,
        ], [
            'label' => ucfirst($typeName),
            'is_active' => true,
        ]);

        return Document::create([
            'user_id' => $user->id,
            'document_type_id' => $type->id,
            'title' => 'Export Response Letter',
            'status' => Document::STATUS_REVISED,
        ]);
    }

    private function commentAttributes(): array
    {
        return [
            'reviewer_label' => 'Reviewer 1',
            'comment_number' => 1,
            'original_comment' => 'Please improve the methodology.',
            'related_section' => 'Metode',
            'priority' => ReviewerComment::PRIORITY_MAJOR,
            'status' => ReviewerComment::STATUS_DONE,
        ];
    }
}
