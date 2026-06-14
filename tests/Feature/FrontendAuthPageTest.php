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
            ->assertSee('Masuk ke workspace')
            ->assertSee('id="loginForm"', false)
            ->assertSee('data-page="login"', false);
    }

    public function test_register_page_is_available(): void
    {
        $this->get('/register')
            ->assertOk()
            ->assertSee('Buat workspace Anda')
            ->assertSee('id="registerForm"', false)
            ->assertSee('data-page="register"', false);
    }

    public function test_dashboard_redirect_destinations_are_available(): void
    {
        $this->get('/dashboard')
            ->assertOk()
            ->assertSee('Login berhasil.')
            ->assertSee('data-page="user-dashboard"', false);

        $this->get('/admin/dashboard')
            ->assertOk()
            ->assertSee('Selamat datang, Admin.')
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
            ->assertSee('Bandingkan versi dokumen');

        $this->get('/articles/42/reviewer-mapping')
            ->assertOk()
            ->assertSee('Reviewer Mapping');
    }
}
