@extends('layouts.app', ['title' => 'Perbandingan Versi | AcadCheck AI'])

@section('page', 'document-comparison')

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

    <section class="comparison-shell">
        <div id="comparisonAlert" class="form-alert hidden" role="alert"></div>

        <section class="comparison-hero">
            <div>
                <a href="/documents/{{ $documentId }}" class="detail-back">Kembali ke detail dokumen</a>
                <p class="eyebrow">Version comparison</p>
                <h1>Perbandingan Versi</h1>
                <p id="comparisonDocumentTitle">Memuat informasi dokumen...</p>
            </div>

            <div class="comparison-hero-note">
                <strong>Ukur dampak revisi</strong>
                <p>Bandingkan skor total dan setiap aspek dari dua versi yang sudah dianalisis.</p>
            </div>
        </section>

        <section id="comparisonLoading" class="library-state">
            <span class="loading-dot"></span>
            <div>
                <strong>Memuat riwayat versi</strong>
                <p>Menyiapkan pilihan versi untuk dibandingkan...</p>
            </div>
        </section>

        <div id="comparisonContent" class="hidden">
            <section class="detail-card comparison-picker">
                <div class="section-heading section-heading-row">
                    <div>
                        <p class="eyebrow">Select versions</p>
                        <h2>Pilih dua versi</h2>
                    </div>
                    <span id="comparisonVersionCount" class="version-badge">0 versi</span>
                </div>

                <div class="comparison-form">
                    <label>
                        <span>Versi awal</span>
                        <select id="fromVersion">
                            <option value="">Pilih versi awal</option>
                        </select>
                    </label>

                    <div class="comparison-direction" aria-hidden="true">ke</div>

                    <label>
                        <span>Versi revisi</span>
                        <select id="toVersion">
                            <option value="">Pilih versi revisi</option>
                        </select>
                    </label>

                    <button type="button" id="compareVersionsButton" class="primary-button">
                        <span class="button-label">Bandingkan versi</span>
                        <span class="button-loader hidden">Membandingkan...</span>
                    </button>
                </div>

                <p class="comparison-hint">Kedua versi harus sudah dianalisis AI terlebih dahulu.</p>
            </section>

            <section id="comparisonEmptyState" class="inline-empty comparison-empty">
                <strong>Pilih versi untuk melihat perubahan.</strong>
                <p>Hasil perbandingan skor total dan skor per aspek akan muncul di sini.</p>
            </section>

            <div id="comparisonResult" class="hidden">
                <section class="comparison-score-grid">
                    <article class="comparison-score-card">
                        <span id="fromVersionLabel">Versi awal</span>
                        <strong id="fromTotalScore">-</strong>
                        <small>Skor total sebelum revisi</small>
                    </article>

                    <article class="comparison-score-card">
                        <span id="toVersionLabel">Versi revisi</span>
                        <strong id="toTotalScore">-</strong>
                        <small>Skor total setelah revisi</small>
                    </article>

                    <article id="totalDifferenceCard" class="comparison-score-card">
                        <span>Perubahan total</span>
                        <strong id="totalDifference">-</strong>
                        <small id="totalStatus">Belum dibandingkan</small>
                    </article>
                </section>

                <section class="detail-card comparison-table-card">
                    <div class="section-heading">
                        <p class="eyebrow">Aspect changes</p>
                        <h2>Perbandingan skor per aspek</h2>
                    </div>

                    <div class="document-table-wrap">
                        <table class="document-table comparison-table">
                            <thead>
                                <tr>
                                    <th>Aspek</th>
                                    <th>Versi awal</th>
                                    <th>Versi revisi</th>
                                    <th>Perubahan</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody id="comparisonTableBody"></tbody>
                        </table>
                    </div>
                </section>
            </div>
        </div>
    </section>
</main>
@endsection
