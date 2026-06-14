@extends('layouts.app', ['title' => 'Document Library | AcadCheck AI'])

@section('page', 'document-library')

@section('content')
<main class="workspace-shell">
    <header class="workspace-header">
        <a href="/dashboard" class="brand-mark workspace-brand" aria-label="AcadCheck AI">
            <span class="brand-icon">A</span>
            <span>AcadCheck <strong>AI</strong></span>
        </a>

        <nav class="workspace-nav" aria-label="Navigasi utama">
            <a href="/dashboard">Dashboard</a>
            <a href="/documents" aria-current="page">Document library</a>
            <button type="button" data-logout>Keluar</button>
        </nav>
    </header>

    <section class="library-shell">
        <div class="library-heading">
            <div>
                <p class="eyebrow">Your archive</p>
                <h1>Document Library</h1>
                <p>Kelola semua dokumen akademik dan lanjutkan proses analisis dari satu tempat.</p>
            </div>

            <a href="/documents/upload" class="primary-button button-link">Upload dokumen</a>
        </div>

        <section class="library-filters" aria-label="Filter dokumen">
            <label class="search-field">
                <span>Cari dokumen</span>
                <input type="search" id="documentSearch" placeholder="Cari judul atau topik...">
            </label>

            <label>
                <span>Jenis</span>
                <select id="documentTypeFilter">
                    <option value="">Semua jenis</option>
                </select>
            </label>

            <label>
                <span>Status</span>
                <select id="documentStatusFilter">
                    <option value="">Semua status</option>
                    <option value="uploaded">Uploaded</option>
                    <option value="analyzed">Analyzed</option>
                    <option value="need_revision">Need revision</option>
                    <option value="revised">Revised</option>
                    <option value="ready">Ready</option>
                </select>
            </label>

            <button type="button" id="clearDocumentFilters" class="filter-reset">Reset filter</button>
        </section>

        <div id="libraryAlert" class="form-alert hidden" role="alert"></div>

        <section id="documentLoading" class="library-state">
            <span class="loading-dot"></span>
            <div>
                <strong>Memuat dokumen</strong>
                <p>Menyiapkan arsip akademik Anda...</p>
            </div>
        </section>

        <section id="documentLibraryContent" class="library-content hidden">
            <div class="library-meta">
                <p><strong id="visibleDocumentCount">0</strong> dokumen ditampilkan</p>
                <span id="totalDocumentCount">0 total dokumen</span>
            </div>

            <div class="document-table-wrap">
                <table class="document-table">
                    <thead>
                        <tr>
                            <th>Dokumen</th>
                            <th>Jenis</th>
                            <th>Status</th>
                            <th>Skor</th>
                            <th>Versi</th>
                            <th>Diperbarui</th>
                            <th><span class="sr-only">Aksi</span></th>
                        </tr>
                    </thead>
                    <tbody id="documentTableBody"></tbody>
                </table>
            </div>

            <div id="documentEmptyState" class="library-empty hidden">
                <span class="brand-icon">A</span>
                <h2>Belum ada dokumen yang cocok.</h2>
                <p id="documentEmptyMessage">Upload dokumen pertama untuk memulai analisis.</p>
                <a href="/documents/upload" class="primary-button button-link">Upload dokumen</a>
            </div>
        </section>
    </section>
</main>
@endsection
