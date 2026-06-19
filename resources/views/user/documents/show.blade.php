@extends('layouts.app', ['title' => 'Detail Dokumen | AcadCheck AI'])

@section('page', 'document-detail')

@section('content')
<main class="workspace-shell" data-document-id="{{ $documentId }}">
    <header class="workspace-header">
        <a href="/dashboard" class="brand-mark workspace-brand" aria-label="AcadCheck AI">
            <span class="brand-icon">A</span>
            <span>AcadCheck <strong>AI</strong></span>
        </a>

        <nav class="workspace-nav" aria-label="Navigasi utama">
            <a href="/dashboard">Dashboard</a>
            <a href="/documents">Document library</a>
            <button type="button" data-logout>Keluar</button>
        </nav>
    </header>

    <section class="detail-shell">
        <div id="detailAlert" class="form-alert hidden" role="alert"></div>

        <section id="documentDetailLoading" class="library-state">
            <span class="loading-dot"></span>
            <div>
                <strong>Memuat detail dokumen</strong>
                <p>Menyiapkan metadata, analisis, dan riwayat versi...</p>
            </div>
        </section>

        <div id="documentDetailContent" class="hidden">
            <section class="detail-hero">
                <div>
                    <a href="/documents" class="detail-back">Kembali ke Document Library</a>
                    <p class="eyebrow" id="detailDocumentType">Dokumen akademik</p>
                    <h1 id="detailDocumentTitle">-</h1>
                    <p id="detailDocumentDescription">-</p>
                </div>

                <div class="detail-score-card">
                    <span>Skor terakhir</span>
                    <strong id="detailLatestScore">-</strong>
                    <div id="detailDocumentStatus" class="status-badge status-uploaded">Uploaded</div>
                </div>
            </section>

            <section class="detail-grid">
                <article class="detail-card metadata-card">
                    <div class="section-heading">
                        <p class="eyebrow">Document metadata</p>
                        <h2>Informasi dokumen</h2>
                    </div>

                    <dl class="metadata-list">
                        <div><dt>Topik / bidang</dt><dd id="detailDocumentTopic">-</dd></div>
                        <div><dt>Kata kunci</dt><dd id="detailDocumentKeywords">-</dd></div>
                        <div><dt>Jumlah versi</dt><dd id="detailVersionCount">0</dd></div>
                        <div><dt>Terakhir diperbarui</dt><dd id="detailUpdatedAt">-</dd></div>
                    </dl>
                </article>

                <article class="detail-card action-card">
                    <div class="section-heading">
                        <p class="eyebrow">Document actions</p>
                        <h2>Lanjutkan pekerjaan</h2>
                    </div>

                    <div class="detail-actions">
                        <button type="button" id="analyzeDocumentButton" class="primary-button">
                            <span class="button-label">Jalankan Analisis AI</span>
                            <span class="button-loader hidden">Menganalisis...</span>
                        </button>
                        <a id="uploadRevisionLink" href="#" class="secondary-button button-link">Upload revisi</a>
                        <a id="comparisonLink" href="#" class="secondary-button button-link">Bandingkan versi</a>
                        <a id="reviewerMappingLink" href="#" class="secondary-button button-link hidden">Reviewer Mapping</a>
                    </div>
                    <p class="action-note">Analisis selalu dijalankan terhadap versi dokumen terbaru.</p>
                </article>
            </section>

            <section class="detail-card analysis-card">
                <div class="section-heading section-heading-row">
                    <div>
                        <p class="eyebrow">Latest AI analysis</p>
                        <h2>Hasil analisis terakhir</h2>
                    </div>
                    <span id="analysisVersionBadge" class="version-badge hidden"></span>
                </div>

                <div id="analysisEmptyState" class="inline-empty">
                    <strong>Belum ada hasil analisis.</strong>
                    <p>Jalankan Analisis AI untuk mendapatkan skor, temuan, dan rekomendasi.</p>
                </div>

                <div id="analysisDetailContent" class="hidden">
                    <div class="analysis-overview">
                        <div><span>Skor total</span><strong id="analysisTotalScore">-</strong></div>
                        <div><span>Status analisis</span><strong id="analysisStatus">-</strong></div>
                        <div><span>Waktu analisis</span><strong id="analysisCreatedAt">-</strong></div>
                    </div>

                    <div class="analysis-summary">
                        <h3>Ringkasan</h3>
                        <p id="analysisSummary">-</p>
                    </div>

                    <div class="analysis-lists">
                        <div>
                            <h3>Masalah utama</h3>
                            <ul id="analysisMainIssues"></ul>
                        </div>
                        <div>
                            <h3>Rekomendasi</h3>
                            <ul id="analysisRecommendations"></ul>
                        </div>
                        <div>
                            <h3>Prioritas revisi</h3>
                            <ul id="analysisPriorities"></ul>
                        </div>
                    </div>

                    <div class="section-subheading">
                        <h3>Skor per aspek</h3>
                    </div>
                    <div class="document-table-wrap">
                        <table class="document-table aspect-table">
                            <thead>
                                <tr>
                                    <th>Aspek</th>
                                    <th>Skor</th>
                                    <th>Status</th>
                                    <th>Temuan</th>
                                    <th>Rekomendasi</th>
                                </tr>
                            </thead>
                            <tbody id="analysisAspectTable"></tbody>
                        </table>
                    </div>
                </div>
            </section>

            <section id="journalRecommendationSection" class="detail-card journal-recommendation-section hidden">
                <div class="section-heading section-heading-row">
                    <div>
                        <p class="eyebrow">AI journal matchmaking</p>
                        <h2>Rekomendasi Jurnal</h2>
                        <p>Sistem mencocokkan artikel dengan database jurnal aktif berdasarkan topik, scope, subject area, dan kesiapan submit.</p>
                    </div>

                    <button type="button" id="generateJournalButton" class="primary-button">
                        <span class="button-label">Cari Rekomendasi Jurnal</span>
                        <span class="button-loader hidden">Memproses...</span>
                    </button>
                </div>

                <div id="journalRecommendationLoading" class="library-state hidden">
                    <span class="loading-dot"></span>
                    <div>
                        <strong>Sedang membuat rekomendasi jurnal</strong>
                        <p>AI sedang mencocokkan artikel dengan jurnal aktif yang tersedia.</p>
                    </div>
                </div>

                <div id="journalRecommendationError" class="form-alert hidden" role="alert"></div>

                <div id="journalRecommendationEmpty" class="inline-empty">
                    <strong>Belum ada rekomendasi jurnal.</strong>
                    <p>Klik tombol Cari Rekomendasi Jurnal untuk membuat rekomendasi dari dataset jurnal aktif.</p>
                </div>

                <div id="journalRecommendationList" class="journal-recommendation-list hidden"></div>

                <p class="journal-recommendation-note">
                    Rekomendasi jurnal bersifat bantuan awal. Tetap verifikasi status jurnal, template, biaya publikasi, dan ketentuan submit melalui website resmi jurnal.
                </p>
            </section>

            <section class="detail-card version-card">
                <div class="section-heading">
                    <p class="eyebrow">Version history</p>
                    <h2>Riwayat versi</h2>
                </div>

                <div class="document-table-wrap">
                    <table class="document-table version-table">
                        <thead>
                            <tr>
                                <th>Versi</th>
                                <th>Nama file</th>
                                <th>Tipe</th>
                                <th>Ukuran</th>
                                <th>Catatan revisi</th>
                                <th>Waktu upload</th>
                            </tr>
                        </thead>
                        <tbody id="documentVersionTable"></tbody>
                    </table>
                </div>
            </section>
        </div>
    </section>
</main>
@endsection
