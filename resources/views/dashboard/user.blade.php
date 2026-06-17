@extends('layouts.app', ['title' => 'Dashboard | AcadCheck AI'])

@section('page', 'user-dashboard')

@section('content')
<main class="workspace-shell user-dashboard-shell">
    <header class="workspace-header">
        <a href="/dashboard" class="brand-mark workspace-brand" aria-label="AcadCheck AI">
            <span class="brand-icon">A</span>
            <span>AcadCheck <strong>AI</strong></span>
        </a>

        <nav class="workspace-nav" aria-label="Navigasi utama">
            <a href="/dashboard" aria-current="page">Dashboard</a>
            <a href="/documents">Document Library</a>
            <button type="button" data-logout>Keluar</button>
        </nav>
    </header>

    <section class="user-dashboard">
        <div id="dashboardAlert" class="form-alert hidden" role="alert"></div>

        <section class="user-dashboard-hero">
            <div>
                <p class="eyebrow">Your workspace</p>
                <h1>Dashboard</h1>
                <p>Pantau perkembangan dokumen akademik, lanjutkan proses analisis, dan kelola revisi dari satu tempat.</p>
                <div class="dashboard-userline" id="currentUser"></div>
            </div>

            <a href="/documents/upload" class="primary-button button-link">Upload dokumen</a>
        </section>

        <section id="dashboardLoading" class="library-state">
            <span class="loading-dot"></span>
            <div>
                <strong>Memuat dashboard</strong>
                <p>Menyiapkan ringkasan dokumen akademik Anda...</p>
            </div>
        </section>

        <div id="dashboardContent" class="hidden">
            <section class="dashboard-card dashboard-status-card">
                <div class="section-heading">
                    <p class="eyebrow">Workspace Status</p>
                    <h2>Status Pekerjaan Anda</h2>
                    <p id="dashboardStatusNarrative">Memuat status dokumen akademik Anda...</p>
                </div>

                <div class="dashboard-status-facts">
                    <span><strong id="needRevision">0</strong> dokumen perlu revisi</span>
                    <span><strong id="readyDocuments">0</strong> dokumen siap dilanjutkan</span>
                    <span>Rata-rata skor <strong id="averageScore">0</strong></span>
                </div>

                <div class="dashboard-status-actions">
                    <a href="/documents?status=need_revision" class="primary-button button-link">Lihat dokumen perlu revisi</a>
                    <a href="/documents" class="secondary-button button-link">Buka Document Library</a>
                </div>
            </section>

            <section class="dashboard-grid">
                <article class="dashboard-card dashboard-priority-card">
                    <div class="section-heading">
                        <p class="eyebrow">Revision Priority</p>
                        <h2>Prioritas Revisi</h2>
                        <p>Mulai dari dokumen yang masih perlu diperbaiki agar proses revisi lebih terarah.</p>
                    </div>

                    <div id="revisionPriorities" class="dashboard-priority-list"></div>
                </article>

                <article class="dashboard-card dashboard-action-card">
                    <div class="section-heading">
                        <p class="eyebrow">Quick Action</p>
                        <h2>Lanjutkan pekerjaan Anda</h2>
                    </div>

                    <div class="dashboard-action-list">
                        <a href="/documents/upload">
                            <span>Upload dokumen baru</span>
                            <strong>&rarr;</strong>
                        </a>
                        <a href="/documents">
                            <span>Buka Document Library</span>
                            <strong>&rarr;</strong>
                        </a>
                        <a href="/documents?status=need_revision">
                            <span>Lihat dokumen perlu revisi</span>
                            <strong>&rarr;</strong>
                        </a>
                    </div>

                    <p>Gunakan dashboard ini untuk melihat perkembangan dokumen sebelum masuk ke proses analisis atau revisi lanjutan.</p>
                </article>
            </section>

            <section class="dashboard-card dashboard-mini-summary">
                <div>
                    <p class="eyebrow">Small Summary</p>
                    <h2>Komposisi Dokumen</h2>
                </div>

                <div class="dashboard-type-strip">
                    <span>Artikel <strong id="totalArticle">0</strong></span>
                    <span>Proposal <strong id="totalProposal">0</strong></span>
                    <span>Laporan <strong id="totalReport">0</strong></span>
                    <span>Total <strong id="totalDocuments">0</strong></span>
                </div>
            </section>

            <section class="dashboard-card dashboard-activity-card">
                <div class="dashboard-section-row">
                    <div class="section-heading">
                        <p class="eyebrow">Recent Archive</p>
                        <h2>Aktivitas Dokumen Terbaru</h2>
                    </div>

                    <a href="/documents" class="secondary-button button-link">Lihat semua</a>
                </div>

                <div class="document-table-wrap dashboard-table-wrap">
                    <table class="document-table dashboard-activity-table">
                        <thead>
                            <tr>
                                <th>Dokumen</th>
                                <th>Jenis</th>
                                <th>Status</th>
                                <th>Skor</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="latestActivities"></tbody>
                    </table>
                </div>
            </section>
        </div>
    </section>
</main>
@endsection
