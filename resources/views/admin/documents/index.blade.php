@extends('layouts.app', ['title' => 'Data Dokumen | AcadCheck AI'])

@section('page', 'admin-documents')

@section('content')
<main class="workspace-shell admin-workspace">
    <header class="workspace-header">
        <a href="/admin/dashboard" class="brand-mark workspace-brand" aria-label="AcadCheck AI Admin">
            <span class="brand-icon">A</span>
            <span>AcadCheck <strong>AI</strong> Admin</span>
        </a>

        <nav class="workspace-nav" aria-label="Navigasi admin">
            <a href="/admin/dashboard">Dashboard Admin</a>
            <a href="/admin/users">Data User</a>
            <span id="adminDocumentsIdentity">Admin</span>
            <button type="button" data-logout>Keluar</button>
        </nav>
    </header>

    <section class="admin-shell">
        <div id="adminDocumentsAlert" class="form-alert hidden" role="alert"></div>

        <section class="admin-hero admin-users-hero">
            <div>
                <p class="eyebrow">Document monitoring</p>
                <h1>Data Dokumen</h1>
                <p>Monitoring semua dokumen yang diunggah user, lengkap dengan status, skor terakhir, versi, dan tanggal upload.</p>
            </div>
            <a href="/admin/dashboard" class="secondary-button button-link">Kembali ke Dashboard</a>
        </section>

        <section class="admin-document-filters">
            <label class="admin-document-search">
                <span>Cari dokumen atau user</span>
                <input id="adminDocumentSearch" type="search" placeholder="Judul, topik, nama, atau email...">
            </label>
            <label>
                <span>Jenis</span>
                <select id="adminDocumentTypeFilter">
                    <option value="">Semua jenis</option>
                    <option value="article">Artikel ilmiah</option>
                    <option value="proposal">Proposal</option>
                    <option value="report">Laporan</option>
                </select>
            </label>
            <label>
                <span>Status</span>
                <select id="adminDocumentStatusFilter">
                    <option value="">Semua status</option>
                    <option value="uploaded">Uploaded</option>
                    <option value="analyzed">Analyzed</option>
                    <option value="need_revision">Need revision</option>
                    <option value="revised">Revised</option>
                    <option value="ready">Ready</option>
                    <option value="archived">Archived</option>
                </select>
            </label>
            <button type="button" id="resetAdminDocumentFilters" class="filter-reset">Reset filter</button>
        </section>

        <section id="adminDocumentsLoading" class="library-state">
            <span class="loading-dot"></span>
            <div>
                <strong>Memuat data dokumen</strong>
                <p>Mengambil dokumen dari seluruh user...</p>
            </div>
        </section>

        <section id="adminDocumentsContent" class="hidden">
            <div class="admin-user-meta">
                <p>Menampilkan <strong id="adminDocumentVisibleCount">0</strong> dari <strong id="adminDocumentTotalCount">0</strong> dokumen</p>
                <p>Halaman <strong id="adminDocumentCurrentPage">1</strong> dari <strong id="adminDocumentLastPage">1</strong></p>
            </div>

            <div class="document-table-wrap">
                <table class="document-table admin-document-management-table">
                    <thead>
                        <tr>
                            <th>Dokumen</th>
                            <th>User</th>
                            <th>Jenis</th>
                            <th>Status</th>
                            <th>Skor</th>
                            <th>Versi</th>
                            <th>Tanggal upload</th>
                        </tr>
                    </thead>
                    <tbody id="adminDocumentTableBody"></tbody>
                </table>
            </div>

            <div class="admin-pagination">
                <button type="button" id="adminDocumentsPreviousPage" class="secondary-button">Sebelumnya</button>
                <span id="adminDocumentsPageLabel">Halaman 1</span>
                <button type="button" id="adminDocumentsNextPage" class="secondary-button">Berikutnya</button>
            </div>
        </section>
    </section>
</main>
@endsection
