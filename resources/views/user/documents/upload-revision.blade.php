@extends('layouts.app', ['title' => 'Upload Revisi | AcadCheck AI'])

@section('page', 'document-revision-upload')

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

    <section class="upload-layout revision-layout">
        <aside class="upload-intro">
            <a href="/documents/{{ $documentId }}" class="detail-back">Kembali ke detail dokumen</a>
            <p class="eyebrow">New revision</p>
            <h1>Tambahkan versi baru dari dokumen Anda.</h1>
            <p>
                File revisi disimpan sebagai versi berikutnya. Skor terakhir akan dikosongkan
                sampai versi baru dianalisis kembali.
            </p>

            <div id="revisionDocumentInfo" class="revision-context">
                <span class="loading-dot"></span>
                <div>
                    <strong>Memuat informasi dokumen</strong>
                    <p>Menentukan versi berikutnya...</p>
                </div>
            </div>
        </aside>

        <div class="upload-card">
            <div class="form-heading">
                <p class="eyebrow">Revision file</p>
                <h2>Upload revisi</h2>
                <p>Gunakan file PDF atau DOCX maksimal 10 MB.</p>
            </div>

            <div id="revisionAlert" class="form-alert hidden" role="alert"></div>

            <form id="uploadRevisionForm" class="document-form" enctype="multipart/form-data" novalidate>
                <label class="file-field field-wide" id="revisionFileDropArea">
                    <input type="file" name="file" id="revisionFile" accept=".pdf,.docx" required>
                    <span class="file-icon">+</span>
                    <strong id="revisionFileLabel">Pilih file revisi PDF atau DOCX</strong>
                    <small id="revisionFileMeta">Ukuran maksimal 10 MB</small>
                </label>

                <label class="field-wide">
                    <span>Catatan revisi</span>
                    <textarea
                        name="revision_note"
                        rows="6"
                        maxlength="5000"
                        placeholder="Contoh: Memperjelas metode, menambah referensi, dan memperbaiki kesimpulan."
                    ></textarea>
                    <small class="field-hint">Opsional, maksimal 5.000 karakter.</small>
                </label>

                <div class="revision-warning field-wide">
                    <strong>Setelah upload</strong>
                    <p>Dokumen berubah ke status Revised dan perlu menjalankan Analisis AI kembali.</p>
                </div>

                <div class="form-actions field-wide">
                    <a href="/documents/{{ $documentId }}" class="secondary-button button-link">Batal</a>
                    <button type="submit" class="primary-button">
                        <span class="button-label">Upload revisi</span>
                        <span class="button-loader hidden">Mengunggah revisi...</span>
                    </button>
                </div>
            </form>
        </div>
    </section>
</main>
@endsection
