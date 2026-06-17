<?php

namespace Tests\Feature;

use Tests\TestCase;

class FrontendAuthPageTest extends TestCase
{
    public function test_home_redirects_to_login_page(): void
    {
        $this->get('/')
            ->assertRedirect('/login');
    }

    public function test_login_page_is_available(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('Masuk Akun')
            ->assertSee('Academic Document Assistant')
            ->assertSee('id="loginForm"', false)
            ->assertSee('data-page="login"', false);
    }

    public function test_register_page_is_available(): void
    {
        $this->get('/register')
            ->assertOk()
            ->assertSee('Daftar Akun')
            ->assertSee('Academic Document Assistant')
            ->assertSee('id="registerForm"', false)
            ->assertSee('data-page="register"', false);
    }

    public function test_dashboard_redirect_destinations_are_available(): void
    {
        $this->get('/dashboard')
            ->assertOk()
            ->assertSee('Dashboard')
            ->assertSee('id="totalDocuments"', false)
            ->assertSee('id="latestActivities"', false)
            ->assertSee('data-page="user-dashboard"', false);

        $this->get('/admin/dashboard')
            ->assertOk()
            ->assertSee('Dashboard Admin')
            ->assertSee('Data User')
            ->assertSee('Data Dokumen')
            ->assertSee('Rubrik AI')
            ->assertSee('id="adminTotalUsers"', false)
            ->assertSee('id="adminLatestDocuments"', false)
            ->assertSee('id="adminLatestAnalyses"', false)
            ->assertSee('data-page="admin-dashboard"', false);
    }

    public function test_document_upload_page_is_available(): void
    {
        $this->get('/documents/upload')
            ->assertOk()
            ->assertSee('Upload dokumen')
            ->assertSee('id="uploadDocumentForm"', false)
            ->assertSee('data-page="document-upload"', false);
    }

    public function test_document_library_page_is_available(): void
    {
        $this->get('/documents')
            ->assertOk()
            ->assertSee('Document Library')
            ->assertSee('id="documentTableBody"', false)
            ->assertSee('id="documentTypeFilter"', false)
            ->assertSee('id="documentStatusFilter"', false)
            ->assertSee('data-page="document-library"', false);
    }

    public function test_document_detail_destination_is_available(): void
    {
        $this->get('/documents/42')
            ->assertOk()
            ->assertSee('Hasil analisis terakhir')
            ->assertSee('id="analyzeDocumentButton"', false)
            ->assertSee('id="documentVersionTable"', false)
            ->assertSee('data-document-id="42"', false)
            ->assertSee('data-page="document-detail"', false);
    }

    public function test_document_action_destinations_are_available(): void
    {
        $this->get('/documents/42/revisions/upload')
            ->assertOk()
            ->assertSee('Upload revisi')
            ->assertSee('id="uploadRevisionForm"', false)
            ->assertSee('data-page="document-revision-upload"', false);

        $this->get('/documents/42/comparison')
            ->assertOk()
            ->assertSee('Perbandingan Versi')
            ->assertSee('id="fromVersion"', false)
            ->assertSee('id="toVersion"', false)
            ->assertSee('id="compareVersionsButton"', false)
            ->assertSee('id="comparisonTableBody"', false)
            ->assertSee('data-page="document-comparison"', false);

        $this->get('/articles/42/reviewer-mapping')
            ->assertOk()
            ->assertSee('Reviewer Mapping')
            ->assertSee('id="reviewerText"', false)
            ->assertSee('id="parseReviewerButton"', false)
            ->assertSee('id="manualCommentForm"', false)
            ->assertSee('id="authorResponseForm"', false)
            ->assertSee('id="responseMatrixTableBody"', false)
            ->assertSee('id="downloadResponseLetterButton"', false)
            ->assertSee('data-page="reviewer-mapping"', false);
    }

    public function test_admin_user_management_page_is_available(): void
    {
        $this->get('/admin/users')
            ->assertOk()
            ->assertSee('Data User')
            ->assertSee('id="adminUserSearch"', false)
            ->assertSee('id="adminUserRoleFilter"', false)
            ->assertSee('id="adminUserStatusFilter"', false)
            ->assertSee('id="adminUserTableBody"', false)
            ->assertSee('id="adminUsersPreviousPage"', false)
            ->assertSee('data-page="admin-users"', false);
    }

    public function test_admin_document_management_page_is_available(): void
    {
        $this->get('/admin/documents')
            ->assertOk()
            ->assertSee('Data Dokumen')
            ->assertSee('id="adminDocumentSearch"', false)
            ->assertSee('id="adminDocumentTypeFilter"', false)
            ->assertSee('id="adminDocumentStatusFilter"', false)
            ->assertSee('id="adminDocumentTableBody"', false)
            ->assertSee('id="adminDocumentsPreviousPage"', false)
            ->assertSee('data-page="admin-documents"', false);
    }

    public function test_admin_rubric_management_page_is_available(): void
    {
        $this->get('/admin/rubrics')
            ->assertOk()
            ->assertSee('Rubrik AI')
            ->assertSee('id="adminRubricTypeFilter"', false)
            ->assertSee('id="adminRubricStatusFilter"', false)
            ->assertSee('id="adminRubricTableBody"', false)
            ->assertSee('data-page="admin-rubrics"', false);
    }
}
