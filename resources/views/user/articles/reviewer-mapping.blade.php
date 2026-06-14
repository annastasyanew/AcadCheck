@extends('layouts.app', ['title' => 'Reviewer Mapping | AcadCheck AI'])

@section('page', 'reviewer-mapping')

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

    <section class="reviewer-shell">
        <div id="reviewerAlert" class="form-alert hidden" role="alert"></div>

        <section class="reviewer-hero">
            <div>
                <a href="/documents/{{ $documentId }}" class="detail-back">Kembali ke detail dokumen</a>
                <p class="eyebrow">Reviewer revision mapping</p>
                <h1>Reviewer Mapping</h1>
                <p id="reviewerArticleTitle">Memuat informasi artikel...</p>
            </div>

            <button type="button" id="downloadResponseLetterButton" class="secondary-button" disabled>
                <span class="button-label">Download Response Letter</span>
                <span class="button-loader hidden">Menyiapkan PDF...</span>
            </button>
        </section>

        <section id="reviewerLoading" class="library-state">
            <span class="loading-dot"></span>
            <div>
                <strong>Memuat reviewer workspace</strong>
                <p>Menyiapkan komentar dan response matrix...</p>
            </div>
        </section>

        <div id="reviewerContent" class="hidden">
            <section class="reviewer-grid">
                <article class="detail-card reviewer-parser-card">
                    <div class="section-heading">
                        <p class="eyebrow">AI comment parser</p>
                        <h2>Paste catatan reviewer</h2>
                    </div>

                    <label class="reviewer-field">
                        <span>Catatan reviewer</span>
                        <textarea
                            id="reviewerText"
                            rows="9"
                            maxlength="30000"
                            placeholder="Reviewer 1:&#10;1. The abstract does not clearly state the main findings.&#10;2. The method section lacks dataset split information."
                        ></textarea>
                    </label>

                    <div class="reviewer-actions">
                        <button type="button" id="parseReviewerButton" class="primary-button">
                            <span class="button-label">Parse dengan AI</span>
                            <span class="button-loader hidden">Memproses komentar...</span>
                        </button>
                        <button type="button" id="toggleManualCommentButton" class="secondary-button">
                            Input manual
                        </button>
                    </div>
                </article>

                <article id="manualCommentCard" class="detail-card reviewer-manual-card hidden">
                    <div class="section-heading">
                        <p class="eyebrow">Manual entry</p>
                        <h2>Tambah komentar</h2>
                    </div>

                    <form id="manualCommentForm" class="reviewer-form">
                        <label>
                            <span>Reviewer</span>
                            <input name="reviewer_label" type="text" value="Reviewer 1" maxlength="100" required>
                        </label>
                        <label>
                            <span>Nomor komentar</span>
                            <input name="comment_number" type="number" min="1">
                        </label>
                        <label>
                            <span>Bagian terkait</span>
                            <input name="related_section" type="text" maxlength="100" placeholder="Metode, Abstrak, Referensi">
                        </label>
                        <label>
                            <span>Prioritas</span>
                            <select name="priority" required>
                                <option value="minor">Minor</option>
                                <option value="major" selected>Major</option>
                                <option value="critical">Critical</option>
                            </select>
                        </label>
                        <label class="reviewer-field-wide">
                            <span>Komentar reviewer</span>
                            <textarea name="original_comment" rows="5" required></textarea>
                        </label>
                        <div class="reviewer-actions reviewer-field-wide">
                            <button type="submit" class="primary-button">
                                <span class="button-label">Simpan komentar</span>
                                <span class="button-loader hidden">Menyimpan...</span>
                            </button>
                        </div>
                    </form>
                </article>
            </section>

            <section class="detail-card reviewer-comment-card">
                <div class="section-heading section-heading-row">
                    <div>
                        <p class="eyebrow">Reviewer comments</p>
                        <h2>Daftar komentar reviewer</h2>
                    </div>
                    <span id="reviewerCommentCount" class="version-badge">0 komentar</span>
                </div>

                <div id="reviewerCommentList" class="reviewer-comment-list"></div>
            </section>

            <section id="responseEditorCard" class="detail-card reviewer-response-card hidden">
                <div class="section-heading section-heading-row">
                    <div>
                        <p class="eyebrow">Author response</p>
                        <h2>Susun respons penulis</h2>
                    </div>
                    <button type="button" id="closeResponseEditorButton" class="secondary-button">Tutup</button>
                </div>

                <input type="hidden" id="selectedReviewerCommentId">
                <div id="selectedReviewerComment" class="selected-reviewer-comment"></div>

                <form id="authorResponseForm" class="reviewer-response-form">
                    <label>
                        <span>Perubahan yang dilakukan</span>
                        <textarea id="revisionMade" rows="4" required></textarea>
                    </label>
                    <label>
                        <span>Lokasi revisi</span>
                        <input id="revisionLocation" type="text" maxlength="255" placeholder="Contoh: Section 3.2, Paragraph 2">
                    </label>
                    <label>
                        <span>Versi revisi terkait</span>
                        <select id="revisedVersionId">
                            <option value="">Tidak ditentukan</option>
                        </select>
                    </label>
                    <div class="reviewer-actions">
                        <button type="button" id="generateAuthorResponseButton" class="secondary-button">
                            <span class="button-label">Generate respons AI</span>
                            <span class="button-loader hidden">Membuat draft...</span>
                        </button>
                    </div>
                    <label>
                        <span>Respons penulis</span>
                        <textarea id="authorResponse" rows="6" required></textarea>
                    </label>
                    <div class="reviewer-actions">
                        <button type="submit" class="primary-button">
                            <span class="button-label">Simpan respons final</span>
                            <span class="button-loader hidden">Menyimpan respons...</span>
                        </button>
                    </div>
                </form>
            </section>

            <section class="detail-card reviewer-matrix-card">
                <div class="section-heading">
                    <p class="eyebrow">Response matrix</p>
                    <h2>Jejak komentar dan revisi</h2>
                </div>

                <div class="document-table-wrap">
                    <table class="document-table reviewer-matrix-table">
                        <thead>
                            <tr>
                                <th>Reviewer</th>
                                <th>Komentar</th>
                                <th>Respons penulis</th>
                                <th>Perubahan</th>
                                <th>Lokasi</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id="responseMatrixTableBody"></tbody>
                    </table>
                </div>
            </section>
        </div>
    </section>
</main>
@endsection
