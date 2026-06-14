<?php

namespace Tests\Feature;

use App\Models\AnalysisResult;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\ReviewerComment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DashboardApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_dashboard_only_summarizes_authenticated_users_data(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $article = $this->createDocument($user, 'article', Document::STATUS_READY, 90);
        $proposal = $this->createDocument($user, 'proposal', Document::STATUS_NEED_REVISION, 60);
        $this->createDocument($user, 'report', Document::STATUS_UPLOADED);
        $this->createDocument($otherUser, 'article', Document::STATUS_READY, 100);
        $this->createAnalysis($article, 90);
        $article->reviewerComments()->create([
            'reviewer_label' => 'Reviewer 1',
            'original_comment' => 'Comment.',
            'priority' => ReviewerComment::PRIORITY_MAJOR,
            'status' => ReviewerComment::STATUS_PENDING,
        ]);
        Sanctum::actingAs($user);

        $this->getJson('/api/user/dashboard')
            ->assertOk()
            ->assertJsonPath('data.summary.total_documents', 3)
            ->assertJsonPath('data.summary.average_score', 75)
            ->assertJsonPath('data.summary.by_type.article', 1)
            ->assertJsonPath('data.summary.by_type.proposal', 1)
            ->assertJsonPath('data.summary.by_type.report', 1)
            ->assertJsonPath('data.summary.by_status.ready', 1)
            ->assertJsonPath('data.summary.by_status.need_revision', 1)
            ->assertJsonPath('data.reviewer_comments.pending', 1)
            ->assertJsonCount(3, 'data.latest_documents')
            ->assertJsonCount(1, 'data.latest_analyses');

        $this->assertNotSame($article->id, $proposal->id);
    }

    public function test_admin_dashboard_summarizes_entire_system(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $user = User::factory()->create();
        User::factory()->create(['is_active' => false]);
        $article = $this->createDocument($user, 'article', Document::STATUS_ANALYZED, 80);
        $this->createDocument($admin, 'report', Document::STATUS_READY, 90);
        $this->createAnalysis($article, 80);
        Sanctum::actingAs($admin);

        $this->getJson('/api/admin/dashboard')
            ->assertOk()
            ->assertJsonPath('data.summary.total_users', 3)
            ->assertJsonPath('data.summary.total_admins', 1)
            ->assertJsonPath('data.summary.total_regular_users', 2)
            ->assertJsonPath('data.summary.active_users', 2)
            ->assertJsonPath('data.summary.inactive_users', 1)
            ->assertJsonPath('data.summary.total_documents', 2)
            ->assertJsonPath('data.summary.total_analysis', 1)
            ->assertJsonPath('data.summary.average_score', 85)
            ->assertJsonCount(2, 'data.latest_documents')
            ->assertJsonCount(1, 'data.latest_analyses');
    }

    public function test_regular_user_cannot_access_admin_dashboard(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/admin/dashboard')
            ->assertForbidden()
            ->assertJsonPath('message', 'Akses hanya untuk admin.');
    }

    private function createDocument(
        User $user,
        string $typeName,
        string $status,
        ?int $score = null,
    ): Document {
        $type = DocumentType::firstOrCreate(
            ['name' => $typeName],
            ['label' => ucfirst($typeName), 'is_active' => true],
        );
        $document = Document::create([
            'user_id' => $user->id,
            'document_type_id' => $type->id,
            'title' => "{$typeName} document",
            'status' => $status,
            'latest_score' => $score,
        ]);
        $version = $document->versions()->create([
            'version_number' => 1,
            'file_path' => "documents/{$document->id}.pdf",
            'file_original_name' => 'document.pdf',
            'file_type' => 'pdf',
            'file_size' => 100,
            'extracted_text' => 'Document text.',
            'uploaded_at' => now(),
        ]);
        $document->update(['latest_version_id' => $version->id]);

        return $document->fresh();
    }

    private function createAnalysis(Document $document, int $score): AnalysisResult
    {
        return AnalysisResult::create([
            'document_id' => $document->id,
            'document_version_id' => $document->latest_version_id,
            'total_score' => $score,
            'status' => 'Cukup',
            'summary' => 'Analysis.',
            'main_issues' => [],
            'recommendations' => [],
            'revision_priorities' => [],
            'raw_ai_response' => [],
        ]);
    }
}
