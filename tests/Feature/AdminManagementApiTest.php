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

class AdminManagementApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_and_filter_users_with_document_counts(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $activeUser = User::factory()->create(['name' => 'Active Scholar']);
        User::factory()->create(['name' => 'Inactive Scholar', 'is_active' => false]);
        $this->createDocument($activeUser, 'article', Document::STATUS_READY);
        Sanctum::actingAs($admin);

        $this->getJson('/api/admin/users?search=Active&role=user&is_active=1&per_page=10')
            ->assertOk()
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.data.0.id', $activeUser->id)
            ->assertJsonPath('data.data.0.documents_count', 1);
    }

    public function test_admin_can_deactivate_user_and_revoke_tokens(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $user = User::factory()->create();
        $user->createToken('test-token');
        Sanctum::actingAs($admin);

        $this->putJson("/api/admin/users/{$user->id}/status", [
            'is_active' => false,
        ])
            ->assertOk()
            ->assertJsonPath('message', 'User berhasil dinonaktifkan.')
            ->assertJsonPath('data.is_active', false);

        $this->assertDatabaseHas('users', ['id' => $user->id, 'is_active' => false]);
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_admin_cannot_deactivate_own_account(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        Sanctum::actingAs($admin);

        $this->putJson("/api/admin/users/{$admin->id}/status", [
            'is_active' => false,
        ])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Admin tidak dapat menonaktifkan akunnya sendiri.');
    }

    public function test_admin_can_list_and_filter_all_documents_with_counts(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $user = User::factory()->create(['name' => 'Document Owner']);
        $article = $this->createDocument($user, 'article', Document::STATUS_READY);
        $this->createDocument($user, 'proposal', Document::STATUS_NEED_REVISION);
        $article->versions()->create([
            'version_number' => 2,
            'file_path' => 'documents/revision.pdf',
            'file_original_name' => 'revision.pdf',
            'file_type' => 'pdf',
            'file_size' => 100,
            'extracted_text' => 'Revision.',
            'uploaded_at' => now(),
        ]);
        $this->createAnalysis($article);
        $article->reviewerComments()->create([
            'reviewer_label' => 'Reviewer 1',
            'original_comment' => 'Comment.',
            'priority' => ReviewerComment::PRIORITY_MAJOR,
            'status' => ReviewerComment::STATUS_PENDING,
        ]);
        Sanctum::actingAs($admin);

        $this->getJson('/api/admin/documents?document_type=article&status=ready&search=Document Owner')
            ->assertOk()
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.data.0.id', $article->id)
            ->assertJsonPath('data.data.0.versions_count', 2)
            ->assertJsonPath('data.data.0.analysis_results_count', 1)
            ->assertJsonPath('data.data.0.reviewer_comments_count', 1);
    }

    public function test_regular_user_cannot_access_admin_management_endpoints(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->getJson('/api/admin/users')->assertForbidden();
        $this->getJson('/api/admin/documents')->assertForbidden();
        $this->putJson("/api/admin/users/{$user->id}/status", ['is_active' => false])->assertForbidden();
    }

    private function createDocument(User $user, string $typeName, string $status): Document
    {
        $type = DocumentType::firstOrCreate(
            ['name' => $typeName],
            ['label' => ucfirst($typeName), 'is_active' => true],
        );
        $document = Document::create([
            'user_id' => $user->id,
            'document_type_id' => $type->id,
            'title' => "{$typeName} by {$user->name}",
            'status' => $status,
            'latest_score' => 80,
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

    private function createAnalysis(Document $document): AnalysisResult
    {
        return AnalysisResult::create([
            'document_id' => $document->id,
            'document_version_id' => $document->latest_version_id,
            'total_score' => 80,
            'status' => 'Cukup',
            'summary' => 'Analysis.',
            'main_issues' => [],
            'recommendations' => [],
            'revision_priorities' => [],
            'raw_ai_response' => [],
        ]);
    }
}
