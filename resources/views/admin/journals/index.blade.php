@extends('layouts.app', ['title' => 'Journal Database | AcadCheck AI'])

@section('page', 'admin-journals')

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
            <a href="/admin/rubrics">Rubrik AI</a>
            <span id="adminJournalsIdentity">Admin</span>
            <button type="button" data-logout>Keluar</button>
        </nav>
    </header>

    <section class="admin-shell">
        <div id="adminJournalsAlert" class="form-alert hidden" role="alert"></div>

        <section class="admin-hero admin-users-hero">
            <div>
                <p class="eyebrow">Journal dataset</p>
                <h1>Journal Database</h1>
                <p>Import dan kurasi dataset jurnal SINTA sebelum digunakan untuk rekomendasi jurnal berbasis AI.</p>
            </div>
            <a href="/admin/dashboard" class="secondary-button button-link">Kembali ke Dashboard</a>
        </section>

        <section class="admin-metric-grid admin-journal-stats-grid" aria-label="Ringkasan statistik jurnal">
            <article class="admin-metric-card">
                <span>Total jurnal</span>
                <strong id="adminJournalStatTotal">0</strong>
                <small>Seluruh data jurnal di database</small>
            </article>
            <article class="admin-metric-card">
                <span>Jurnal aktif</span>
                <strong id="adminJournalStatActive">0</strong>
                <small>Dipakai sebagai kandidat rekomendasi</small>
            </article>
            <article class="admin-metric-card">
                <span>AI Ready</span>
                <strong id="adminJournalStatAiReady">0</strong>
                <small>Aktif, verified, dan eligibility score minimal 70</small>
            </article>
            <article class="admin-metric-card">
                <span>Pending review</span>
                <strong id="adminJournalStatPending">0</strong>
                <small>Masih perlu dicek admin</small>
            </article>
            <article class="admin-metric-card">
                <span>Verified</span>
                <strong id="adminJournalStatVerified">0</strong>
                <small>Sudah dikurasi admin</small>
            </article>
        </section>

        <section class="detail-card admin-journal-sinta-card">
            <div class="section-heading">
                <p class="eyebrow">SINTA distribution</p>
                <h2>Distribusi SINTA</h2>
                <p>Ringkasan jumlah jurnal per level SINTA untuk membantu memilih dataset demo yang seimbang.</p>
            </div>
            <div id="adminJournalSintaStats" class="admin-journal-sinta-grid"></div>
        </section>

        <section class="detail-card admin-journal-import">
            <div class="section-heading">
                <p class="eyebrow">CSV import</p>
                <h2>Import CSV Jurnal</h2>
                <p>Kolom minimal: name, sinta_level, subject_area, dan website_url. Data import masuk sebagai pending review dan nonaktif.</p>
            </div>

            <form id="adminJournalImportForm" class="admin-journal-import-form">
                <label class="file-field admin-journal-file-field" id="adminJournalFileField">
                    <span class="file-icon">CSV</span>
                    <strong id="adminJournalFileName">Pilih file CSV jurnal</strong>
                    <small>Format .csv atau .txt</small>
                    <input id="adminJournalCsvFile" name="file" type="file" accept=".csv,.txt" required>
                </label>

                <button type="submit" class="primary-button">
                    <span class="button-label">Import CSV</span>
                    <span class="button-loader hidden">Mengimport...</span>
                </button>
            </form>
        </section>

        <section class="admin-document-filters admin-journal-filters">
            <label class="admin-document-search">
                <span>Cari jurnal</span>
                <input id="adminJournalSearch" type="search" placeholder="Nama, publisher, subject, keyword...">
            </label>
            <label>
                <span>SINTA</span>
                <select id="adminJournalSintaFilter">
                    <option value="">Semua SINTA</option>
                    <option value="S1">SINTA 1</option>
                    <option value="S2">SINTA 2</option>
                    <option value="S3">SINTA 3</option>
                    <option value="S4">SINTA 4</option>
                    <option value="S5">SINTA 5</option>
                    <option value="S6">SINTA 6</option>
                </select>
            </label>
            <label>
                <span>Status</span>
                <select id="adminJournalActiveFilter">
                    <option value="">Semua status</option>
                    <option value="true">Aktif</option>
                    <option value="false">Nonaktif</option>
                </select>
            </label>
            <label>
                <span>Verifikasi</span>
                <select id="adminJournalVerificationFilter">
                    <option value="">Semua verifikasi</option>
                    <option value="pending_review">Pending review</option>
                    <option value="verified">Verified</option>
                </select>
            </label>
            <button type="button" id="resetAdminJournalFilters" class="filter-reset">Reset filter</button>
        </section>

        <section id="adminJournalsLoading" class="library-state">
            <span class="loading-dot"></span>
            <div>
                <strong>Memuat data jurnal</strong>
                <p>Mengambil dataset jurnal dari database...</p>
            </div>
        </section>

        <section id="adminJournalsContent" class="hidden">
            <div class="admin-user-meta">
                <p>Menampilkan <strong id="adminJournalVisibleCount">0</strong> dari <strong id="adminJournalTotalCount">0</strong> jurnal</p>
                <p>Halaman <strong id="adminJournalCurrentPage">1</strong> dari <strong id="adminJournalLastPage">1</strong></p>
            </div>

            <div class="document-table-wrap">
                <table class="document-table admin-journal-table">
                    <thead>
                        <tr>
                            <th>Jurnal</th>
                            <th>SINTA</th>
                            <th>Subject</th>
                            <th>Website</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="adminJournalTableBody"></tbody>
                </table>
            </div>

            <div class="admin-pagination">
                <button type="button" id="adminJournalsPreviousPage" class="secondary-button">Sebelumnya</button>
                <span id="adminJournalsPageLabel">Halaman 1</span>
                <button type="button" id="adminJournalsNextPage" class="secondary-button">Berikutnya</button>
            </div>
        </section>
    </section>

    <div id="adminJournalEditModal" class="admin-journal-modal hidden" role="dialog" aria-modal="true" aria-labelledby="adminJournalEditTitle">
        <div class="admin-journal-modal-panel">
            <div class="section-heading section-heading-row">
                <div>
                    <p class="eyebrow">Journal curation</p>
                    <h2 id="adminJournalEditTitle">Edit Detail Jurnal</h2>
                    <p>Lengkapi metadata penting sebelum jurnal diaktifkan untuk rekomendasi AI.</p>
                </div>

                <button type="button" id="closeAdminJournalEditModal" class="secondary-button">Tutup</button>
            </div>

            <div id="adminJournalEditAlert" class="form-alert hidden" role="alert"></div>

            <form id="adminJournalEditForm" class="admin-journal-edit-form">
                <input type="hidden" id="editJournalId" name="id">

                <label>
                    <span>Nama jurnal</span>
                    <input id="editJournalName" name="name" type="text" required>
                </label>

                <label>
                    <span>Publisher</span>
                    <input id="editJournalPublisher" name="publisher" type="text">
                </label>

                <label>
                    <span>SINTA level</span>
                    <select id="editJournalSintaLevel" name="sinta_level">
                        <option value="">Belum ditentukan</option>
                        <option value="S1">SINTA 1</option>
                        <option value="S2">SINTA 2</option>
                        <option value="S3">SINTA 3</option>
                        <option value="S4">SINTA 4</option>
                        <option value="S5">SINTA 5</option>
                        <option value="S6">SINTA 6</option>
                    </select>
                </label>

                <label>
                    <span>Subject area</span>
                    <input id="editJournalSubjectArea" name="subject_area" type="text">
                </label>

                <label class="admin-journal-edit-wide">
                    <span>Focus scope</span>
                    <textarea id="editJournalFocusScope" name="focus_scope" rows="5"></textarea>
                </label>

                <label class="admin-journal-edit-wide">
                    <span>Keywords</span>
                    <textarea id="editJournalKeywords" name="keywords" rows="3"></textarea>
                </label>

                <label>
                    <span>Website URL</span>
                    <input id="editJournalWebsiteUrl" name="website_url" type="url" placeholder="https://...">
                </label>

                <label>
                    <span>Template URL</span>
                    <input id="editJournalTemplateUrl" name="template_url" type="url" placeholder="https://...">
                </label>

                <label>
                    <span>Author guideline URL</span>
                    <input id="editJournalAuthorGuidelineUrl" name="author_guideline_url" type="url" placeholder="https://...">
                </label>

                <label>
                    <span>Status aktif</span>
                    <select id="editJournalIsActive" name="is_active">
                        <option value="false">Nonaktif</option>
                        <option value="true">Aktif</option>
                    </select>
                </label>

                <label>
                    <span>Status verifikasi</span>
                    <select id="editJournalVerificationStatus" name="verification_status">
                        <option value="pending_review">Pending review</option>
                        <option value="verified">Verified</option>
                    </select>
                </label>

                <div class="admin-journal-edit-actions">
                    <button type="button" id="cancelAdminJournalEdit" class="secondary-button">Batal</button>
                    <button type="submit" class="primary-button">
                        <span class="button-label">Simpan Perubahan</span>
                        <span class="button-loader hidden">Menyimpan...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</main>
@endsection
