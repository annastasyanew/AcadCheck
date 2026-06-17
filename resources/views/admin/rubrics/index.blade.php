@extends('layouts.app', ['title' => 'Rubrik AI | AcadCheck AI'])

@section('page', 'admin-rubrics')

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
            <a href="/admin/documents">Data Dokumen</a>
            <span id="adminRubricsIdentity">Admin</span>
            <button type="button" data-logout>Keluar</button>
        </nav>
    </header>

    <section class="admin-shell">
        <div id="adminRubricsAlert" class="form-alert hidden" role="alert"></div>

        <section class="admin-hero admin-users-hero">
            <div>
                <p class="eyebrow">AI assessment rubric</p>
                <h1>Rubrik AI</h1>
                <p>Kelola aspek penilaian, bobot, deskripsi, dan status aktif rubrik untuk artikel, proposal, dan laporan.</p>
            </div>
            <a href="/admin/dashboard" class="secondary-button button-link">Kembali ke Dashboard</a>
        </section>

        <section class="admin-rubric-filters">
            <label>
                <span>Jenis dokumen</span>
                <select id="adminRubricTypeFilter">
                    <option value="">Semua jenis</option>
                    <option value="article">Artikel ilmiah</option>
                    <option value="proposal">Proposal</option>
                    <option value="report">Laporan</option>
                </select>
            </label>
            <label>
                <span>Status</span>
                <select id="adminRubricStatusFilter">
                    <option value="">Semua status</option>
                    <option value="active">Aktif</option>
                    <option value="inactive">Nonaktif</option>
                </select>
            </label>
            <button type="button" id="resetAdminRubricFilters" class="filter-reset">Reset filter</button>
        </section>

        <section id="adminRubricsLoading" class="library-state">
            <span class="loading-dot"></span>
            <div>
                <strong>Memuat rubrik AI</strong>
                <p>Mengambil aspek penilaian untuk semua jenis dokumen...</p>
            </div>
        </section>

        <section id="adminRubricsContent" class="hidden">
            <div class="admin-user-meta">
                <p>Menampilkan <strong id="adminRubricVisibleCount">0</strong> dari <strong id="adminRubricTotalCount">0</strong> rubrik</p>
                <p>Total bobot terlihat: <strong id="adminRubricVisibleWeight">0</strong>%</p>
            </div>

            <div class="document-table-wrap">
                <table class="document-table admin-rubric-table">
                    <thead>
                        <tr>
                            <th>Jenis</th>
                            <th>Aspek</th>
                            <th>Bobot</th>
                            <th>Deskripsi</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="adminRubricTableBody"></tbody>
                </table>
            </div>
        </section>
    </section>
</main>
@endsection
