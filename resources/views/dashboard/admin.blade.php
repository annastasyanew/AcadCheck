@extends('layouts.app', ['title' => 'Admin Dashboard | AcadCheck AI'])

@section('page', 'admin-dashboard')

@section('content')
<main class="workspace-shell admin-workspace">
    <header class="workspace-header">
        <a href="/admin/dashboard" class="brand-mark workspace-brand" aria-label="AcadCheck AI Admin">
            <span class="brand-icon">A</span>
            <span>AcadCheck <strong>AI</strong> Admin</span>
        </a>

        <nav class="workspace-nav" aria-label="Navigasi admin">
            <span id="adminIdentity">Admin</span>
            <button type="button" data-logout>Keluar</button>
        </nav>
    </header>

    <section class="admin-shell">
        <div id="adminDashboardAlert" class="form-alert hidden" role="alert"></div>

        <section class="admin-hero">
            <div>
                <p class="eyebrow">System monitoring</p>
                <h1>Dashboard Admin</h1>
                <p>Pantau penggunaan, aktivitas analisis, dan kesehatan dokumen AcadCheck AI.</p>
            </div>
            <div class="admin-live-indicator">
                <span class="loading-dot"></span>
                <div>
                    <strong>Monitoring aktif</strong>
                    <p>Data ditarik langsung dari sistem.</p>
                </div>
            </div>
        </section>

        <section id="adminDashboardLoading" class="library-state">
            <span class="loading-dot"></span>
            <div>
                <strong>Memuat dashboard admin</strong>
                <p>Menghitung pengguna, dokumen, dan analisis terbaru...</p>
            </div>
        </section>

        <div id="adminDashboardContent" class="hidden">
            <section class="admin-metric-grid">
                <article class="admin-metric-card">
                    <span>Total pengguna</span>
                    <strong id="adminTotalUsers">0</strong>
                    <small><b id="adminActiveUsers">0</b> aktif, <b id="adminInactiveUsers">0</b> nonaktif</small>
                </article>
                <article class="admin-metric-card">
                    <span>Total dokumen</span>
                    <strong id="adminTotalDocuments">0</strong>
                    <small>Seluruh dokumen dalam sistem</small>
                </article>
                <article class="admin-metric-card">
                    <span>Total analisis</span>
                    <strong id="adminTotalAnalysis">0</strong>
                    <small>Analisis AI yang tersimpan</small>
                </article>
                <article class="admin-metric-card">
                    <span>Rata-rata skor</span>
                    <strong id="adminAverageScore">0</strong>
                    <small>Dari skor terbaru dokumen</small>
                </article>
            </section>

            <section class="admin-overview-grid">
                <article class="detail-card admin-breakdown-card">
                    <div class="section-heading">
                        <p class="eyebrow">Document types</p>
                        <h2>Distribusi jenis</h2>
                    </div>
                    <div class="admin-breakdown-list">
                        <div><span>Artikel ilmiah</span><strong id="adminArticleCount">0</strong></div>
                        <div><span>Proposal</span><strong id="adminProposalCount">0</strong></div>
                        <div><span>Laporan</span><strong id="adminReportCount">0</strong></div>
                    </div>
                </article>

                <article class="detail-card admin-breakdown-card">
                    <div class="section-heading">
                        <p class="eyebrow">Document status</p>
                        <h2>Status dokumen</h2>
                    </div>
                    <div id="adminStatusBreakdown" class="admin-status-grid"></div>
                </article>
            </section>

            <section class="detail-card admin-menu-card">
                <div class="section-heading">
                    <p class="eyebrow">Admin menu</p>
                    <h2>Kelola sistem</h2>
                </div>

                <div class="admin-menu-grid">
                    <a href="/admin/users">
                        <span>01</span>
                        <strong>Data User</strong>
                        <p>Lihat pengguna dan kelola status akun.</p>
                        <small>Tersedia</small>
                    </a>
                    <a href="/admin/documents">
                        <span>02</span>
                        <strong>Data Dokumen</strong>
                        <p>Pantau seluruh dokumen beserta pemiliknya.</p>
                        <small>Tersedia</small>
                    </a>
                    <a href="/admin/rubrics">
                        <span>03</span>
                        <strong>Rubrik AI</strong>
                        <p>Kelola bobot dan deskripsi aspek penilaian.</p>
                        <small>Tersedia</small>
                    </a>
                </div>
            </section>

            <section class="admin-table-grid">
                <article class="detail-card admin-table-card">
                    <div class="section-heading">
                        <p class="eyebrow">Latest documents</p>
                        <h2>Dokumen terbaru</h2>
                    </div>
                    <div class="document-table-wrap">
                        <table class="document-table admin-document-table">
                            <thead>
                                <tr>
                                    <th>Dokumen</th>
                                    <th>Pemilik</th>
                                    <th>Jenis</th>
                                    <th>Status</th>
                                    <th>Skor</th>
                                </tr>
                            </thead>
                            <tbody id="adminLatestDocuments"></tbody>
                        </table>
                    </div>
                </article>

                <article class="detail-card admin-table-card">
                    <div class="section-heading">
                        <p class="eyebrow">Latest analyses</p>
                        <h2>Analisis terbaru</h2>
                    </div>
                    <div class="document-table-wrap">
                        <table class="document-table admin-analysis-table">
                            <thead>
                                <tr>
                                    <th>Dokumen</th>
                                    <th>Pemilik</th>
                                    <th>Versi</th>
                                    <th>Skor</th>
                                    <th>Waktu</th>
                                </tr>
                            </thead>
                            <tbody id="adminLatestAnalyses"></tbody>
                        </table>
                    </div>
                </article>
            </section>
        </div>
    </section>
</main>
@endsection
