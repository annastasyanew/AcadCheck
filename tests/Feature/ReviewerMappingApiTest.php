<?php

namespace Tests\Feature;

use App\Exceptions\GroqException;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\DocumentVersion;
use App\Models\ReviewerComment;
use App\Models\User;
use App\Services\AuthorResponseGeneratorService;
use App\Services\ReviewerCommentParserService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Mockery\MockInterface;
use Tests\TestCase;

class ReviewerMappingApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_create_list_update_status_and_delete_reviewer_comment(): void
    {
        $user = User::factory()->create();
        [$article] = $this->createDocument($user, 'article');
        Sanctum::actingAs($user);

        $commentId = $this->postJson("/api/articles/{$article->id}/reviewer-comments", [
            'reviewer_label' => 'Reviewer 1',
            'comment_number' => 1,
            'original_comment' => 'The methodology section needs more detail.',
            'related_section' => 'Metode',
            'priority' => ReviewerComment::PRIORITY_MAJOR,
        ])
            ->assertCreated()
            ->assertJsonPath('message', 'Komentar reviewer berhasil ditambahkan.')
            ->assertJsonPath('data.status', ReviewerComment::STATUS_PENDING)
            ->json('data.id');

        $this->getJson("/api/articles/{$article->id}/reviewer-comments")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $commentId);

        $this->putJson("/api/reviewer-comments/{$commentId}", [
            'priority' => ReviewerComment::PRIORITY_CRITICAL,
            'related_section' => 'Metode Penelitian',
        ])
            ->assertOk()
            ->assertJsonPath('data.priority', ReviewerComment::PRIORITY_CRITICAL);

        $this->putJson("/api/reviewer-comments/{$commentId}/status", [
            'status' => ReviewerComment::STATUS_IN_PROGRESS,
        ])
            ->assertOk()
            ->assertJsonPath('data.status', ReviewerComment::STATUS_IN_PROGRESS);

        $this->deleteJson("/api/reviewer-comments/{$commentId}")
            ->assertOk()
            ->assertJsonPath('message', 'Komentar reviewer berhasil dihapus.');

        $this->assertDatabaseCount('reviewer_comments', 0);
    }

    public function test_reviewer_mapping_is_only_available_for_articles(): void
    {
        $user = User::factory()->create();
        [$proposal] = $this->createDocument($user, 'proposal');
        Sanctum::actingAs($user);

        $this->postJson("/api/articles/{$proposal->id}/reviewer-comments", [
            'reviewer_label' => 'Reviewer 1',
            'original_comment' => 'Komentar.',
            'priority' => ReviewerComment::PRIORITY_MAJOR,
        ])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Reviewer Revision Mapping hanya tersedia untuk artikel ilmiah.');

        $this->getJson("/api/articles/{$proposal->id}/response-matrix")
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Response matrix hanya tersedia untuk artikel ilmiah.');
    }

    public function test_other_user_cannot_manage_comments_but_admin_can_list_them(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        [$article] = $this->createDocument($owner, 'article');
        $comment = $article->reviewerComments()->create($this->commentAttributes());

        Sanctum::actingAs($otherUser);
        $this->putJson("/api/reviewer-comments/{$comment->id}", [
            'priority' => ReviewerComment::PRIORITY_MINOR,
        ])->assertForbidden();
        $this->getJson("/api/articles/{$article->id}/response-matrix")->assertForbidden();

        Sanctum::actingAs($admin);
        $this->getJson("/api/articles/{$article->id}/reviewer-comments")
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_response_is_saved_updated_and_exposed_in_response_matrix(): void
    {
        $user = User::factory()->create();
        [$article, $firstVersion] = $this->createDocument($user, 'article');
        $secondVersion = $this->createVersion($article, 2);
        $comment = $article->reviewerComments()->create($this->commentAttributes());
        Sanctum::actingAs($user);

        $this->postJson("/api/reviewer-comments/{$comment->id}/responses", [
            'author_response' => 'Thank you. We revised the methodology.',
            'revision_made' => 'Menambahkan detail dataset dan metrik.',
            'revision_location' => 'Section 3.2',
            'revised_version_id' => $secondVersion->id,
        ])
            ->assertOk()
            ->assertJsonPath('message', 'Respons penulis berhasil disimpan.')
            ->assertJsonPath('data.revised_version_id', $secondVersion->id);

        $this->assertDatabaseHas('reviewer_comments', [
            'id' => $comment->id,
            'status' => ReviewerComment::STATUS_DONE,
        ]);

        $this->postJson("/api/reviewer-comments/{$comment->id}/responses", [
            'author_response' => 'Updated author response.',
            'revision_made' => 'Updated revision.',
            'revision_location' => 'Section 3.3',
            'revised_version_id' => $secondVersion->id,
        ])->assertOk();

        $this->assertDatabaseCount('reviewer_responses', 1);
        $this->getJson("/api/articles/{$article->id}/response-matrix")
            ->assertOk()
            ->assertJsonPath('data.document_id', $article->id)
            ->assertJsonPath('data.response_matrix.0.author_response', 'Updated author response.')
            ->assertJsonPath('data.response_matrix.0.revised_version_id', $secondVersion->id)
            ->assertJsonPath('data.response_matrix.0.revised_version_number', 2);

        $this->assertNotSame($firstVersion->id, $secondVersion->id);
    }

    public function test_response_rejects_revised_version_from_another_document(): void
    {
        $user = User::factory()->create();
        [$article] = $this->createDocument($user, 'article');
        [$otherArticle, $otherVersion] = $this->createDocument($user, 'article');
        $comment = $article->reviewerComments()->create($this->commentAttributes());
        Sanctum::actingAs($user);

        $this->postJson("/api/reviewer-comments/{$comment->id}/responses", [
            'author_response' => 'Response.',
            'revised_version_id' => $otherVersion->id,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('revised_version_id');

        $this->assertDatabaseCount('reviewer_responses', 0);
        $this->assertNotSame($article->id, $otherArticle->id);
    }

    public function test_deleting_comment_also_deletes_its_response(): void
    {
        $user = User::factory()->create();
        [$article] = $this->createDocument($user, 'article');
        $comment = $article->reviewerComments()->create($this->commentAttributes());
        $comment->response()->create([
            'author_response' => 'Response.',
        ]);
        Sanctum::actingAs($user);

        $this->deleteJson("/api/reviewer-comments/{$comment->id}")->assertOk();

        $this->assertDatabaseCount('reviewer_comments', 0);
        $this->assertDatabaseCount('reviewer_responses', 0);
    }

    public function test_owner_can_preview_ai_parsed_comments_without_saving(): void
    {
        $user = User::factory()->create();
        [$article] = $this->createDocument($user, 'article');
        Sanctum::actingAs($user);
        $this->mock(ReviewerCommentParserService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('parse')
                ->once()
                ->andReturn(['comments' => [$this->parsedComment()]]);
        });

        $this->postJson("/api/articles/{$article->id}/reviewer-comments/parse", [
            'reviewer_text' => 'Reviewer 1: Please improve the methodology.',
            'save_to_database' => false,
        ])
            ->assertOk()
            ->assertJsonPath('message', 'Komentar reviewer berhasil diproses AI.')
            ->assertJsonCount(1, 'data.parsed_comments')
            ->assertJsonCount(0, 'data.saved_comments');

        $this->assertDatabaseCount('reviewer_comments', 0);
    }

    public function test_owner_can_save_ai_parsed_comments_to_database(): void
    {
        $user = User::factory()->create();
        [$article] = $this->createDocument($user, 'article');
        Sanctum::actingAs($user);
        $this->mock(ReviewerCommentParserService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('parse')
                ->once()
                ->andReturn([
                    'comments' => [
                        $this->parsedComment(),
                        array_merge($this->parsedComment(), [
                            'comment_number' => 2,
                            'original_comment' => 'Please update the references.',
                            'related_section' => 'Referensi',
                            'priority' => ReviewerComment::PRIORITY_MINOR,
                        ]),
                    ],
                ]);
        });

        $this->postJson("/api/articles/{$article->id}/reviewer-comments/parse", [
            'reviewer_text' => 'Reviewer comments.',
            'save_to_database' => true,
        ])
            ->assertOk()
            ->assertJsonCount(2, 'data.parsed_comments')
            ->assertJsonCount(2, 'data.saved_comments')
            ->assertJsonPath('data.saved_comments.0.document_id', $article->id);

        $this->assertDatabaseCount('reviewer_comments', 2);
        $this->assertDatabaseHas('reviewer_comments', [
            'document_id' => $article->id,
            'status' => ReviewerComment::STATUS_PENDING,
        ]);
    }

    public function test_ai_parser_is_article_only_and_requires_document_access(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        [$proposal] = $this->createDocument($owner, 'proposal');
        [$article] = $this->createDocument($owner, 'article');

        Sanctum::actingAs($owner);
        $this->postJson("/api/articles/{$proposal->id}/reviewer-comments/parse", [
            'reviewer_text' => 'Comment.',
        ])->assertUnprocessable();

        Sanctum::actingAs($otherUser);
        $this->postJson("/api/articles/{$article->id}/reviewer-comments/parse", [
            'reviewer_text' => 'Comment.',
        ])->assertForbidden();
    }

    public function test_ai_parser_failure_does_not_save_comments_or_expose_error(): void
    {
        $user = User::factory()->create();
        [$article] = $this->createDocument($user, 'article');
        Sanctum::actingAs($user);
        $this->mock(ReviewerCommentParserService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('parse')
                ->once()
                ->andThrow(new GroqException('Sensitive upstream response.'));
        });

        $this->postJson("/api/articles/{$article->id}/reviewer-comments/parse", [
            'reviewer_text' => 'Reviewer comments.',
            'save_to_database' => true,
        ])
            ->assertStatus(502)
            ->assertJsonPath('message', 'Gagal memproses komentar reviewer dengan AI. Silakan coba kembali.')
            ->assertJsonMissing(['error' => 'Sensitive upstream response.']);

        $this->assertDatabaseCount('reviewer_comments', 0);
    }

    public function test_owner_can_preview_generated_author_response_without_saving(): void
    {
        $user = User::factory()->create();
        [$article] = $this->createDocument($user, 'article');
        $comment = $article->reviewerComments()->create($this->commentAttributes());
        Sanctum::actingAs($user);
        $this->mock(AuthorResponseGeneratorService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('generate')
                ->once()
                ->andReturn($this->generatedResponse());
        });

        $this->postJson("/api/reviewer-comments/{$comment->id}/generate-response", [
            'revision_made' => 'Menambahkan detail metode.',
            'revision_location' => 'Section 3.2',
            'save_to_database' => false,
        ])
            ->assertOk()
            ->assertJsonPath('message', 'Draft respons penulis berhasil dibuat.')
            ->assertJsonPath('data.generated_response.tone', 'polite')
            ->assertJsonPath('data.saved_response', null);

        $this->assertDatabaseCount('reviewer_responses', 0);
        $this->assertDatabaseHas('reviewer_comments', [
            'id' => $comment->id,
            'status' => ReviewerComment::STATUS_PENDING,
        ]);
    }

    public function test_owner_can_generate_save_response_and_update_matrix(): void
    {
        $user = User::factory()->create();
        [$article, $firstVersion] = $this->createDocument($user, 'article');
        $comment = $article->reviewerComments()->create($this->commentAttributes());
        $comment->response()->create([
            'author_response' => 'Old response.',
            'revised_version_id' => $firstVersion->id,
        ]);
        Sanctum::actingAs($user);
        $this->mock(AuthorResponseGeneratorService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('generate')
                ->once()
                ->andReturn($this->generatedResponse());
        });

        $this->postJson("/api/reviewer-comments/{$comment->id}/generate-response", [
            'revision_made' => 'Menambahkan detail metode.',
            'revision_location' => 'Section 3.2',
            'save_to_database' => true,
        ])
            ->assertOk()
            ->assertJsonPath('data.saved_response.author_response', 'Thank you for the valuable comment.')
            ->assertJsonPath('data.saved_response.revised_version_id', $firstVersion->id);

        $this->assertDatabaseCount('reviewer_responses', 1);
        $this->assertDatabaseHas('reviewer_comments', [
            'id' => $comment->id,
            'status' => ReviewerComment::STATUS_DONE,
        ]);

        $this->getJson("/api/articles/{$article->id}/response-matrix")
            ->assertOk()
            ->assertJsonPath(
                'data.response_matrix.0.author_response',
                'Thank you for the valuable comment.',
            )
            ->assertJsonPath('data.response_matrix.0.revision_made', 'Menambahkan detail metode.')
            ->assertJsonPath('data.response_matrix.0.revision_location', 'Section 3.2');
    }

    public function test_author_response_generator_is_article_only_and_requires_access(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        [$proposal] = $this->createDocument($owner, 'proposal');
        [$article] = $this->createDocument($owner, 'article');
        $proposalComment = $proposal->reviewerComments()->create($this->commentAttributes());
        $articleComment = $article->reviewerComments()->create($this->commentAttributes());

        Sanctum::actingAs($owner);
        $this->postJson("/api/reviewer-comments/{$proposalComment->id}/generate-response", [
            'revision_made' => 'Revision.',
        ])->assertUnprocessable();

        Sanctum::actingAs($otherUser);
        $this->postJson("/api/reviewer-comments/{$articleComment->id}/generate-response", [
            'revision_made' => 'Revision.',
        ])->assertForbidden();
    }

    public function test_author_response_generator_failure_does_not_save_or_expose_error(): void
    {
        $user = User::factory()->create();
        [$article] = $this->createDocument($user, 'article');
        $comment = $article->reviewerComments()->create($this->commentAttributes());
        Sanctum::actingAs($user);
        $this->mock(AuthorResponseGeneratorService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('generate')
                ->once()
                ->andThrow(new GroqException('Sensitive upstream response.'));
        });

        $this->postJson("/api/reviewer-comments/{$comment->id}/generate-response", [
            'revision_made' => 'Revision.',
            'save_to_database' => true,
        ])
            ->assertStatus(502)
            ->assertJsonPath('message', 'Gagal membuat draft respons penulis dengan AI. Silakan coba kembali.')
            ->assertJsonMissing(['error' => 'Sensitive upstream response.']);

        $this->assertDatabaseCount('reviewer_responses', 0);
    }

    private function createDocument(User $user, string $typeName): array
    {
        $type = DocumentType::firstOrCreate([
            'name' => $typeName,
        ], [
            'label' => ucfirst($typeName),
            'is_active' => true,
        ]);
        $document = Document::create([
            'user_id' => $user->id,
            'document_type_id' => $type->id,
            'title' => 'Dokumen Reviewer',
            'status' => Document::STATUS_REVISED,
        ]);
        $version = $this->createVersion($document, 1);
        $document->update(['latest_version_id' => $version->id]);

        return [$document->fresh(), $version];
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

    private function commentAttributes(): array
    {
        return [
            'reviewer_label' => 'Reviewer 1',
            'comment_number' => 1,
            'original_comment' => 'Please improve the methodology.',
            'related_section' => 'Metode',
            'priority' => ReviewerComment::PRIORITY_MAJOR,
            'status' => ReviewerComment::STATUS_PENDING,
        ];
    }

    private function parsedComment(): array
    {
        return [
            'reviewer_label' => 'Reviewer 1',
            'comment_number' => 1,
            'original_comment' => 'Please improve the methodology.',
            'related_section' => 'Metode',
            'priority' => ReviewerComment::PRIORITY_MAJOR,
            'status' => ReviewerComment::STATUS_PENDING,
        ];
    }

    private function generatedResponse(): array
    {
        return [
            'author_response' => 'Thank you for the valuable comment.',
            'author_response_id' => 'Terima kasih atas komentar yang diberikan.',
            'revision_summary' => 'Menambahkan detail metode.',
            'tone' => 'polite',
        ];
    }
}
