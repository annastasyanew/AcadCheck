@extends('layouts.app', ['title' => 'Upload Dokumen | AcadCheck AI'])

@section('page', 'document-upload')

@section('content')
<main class="workspace-shell">
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

    <section class="upload-layout">
        <aside class="upload-intro">
            <p class="eyebrow">New document</p>
            <h1>Unggah dokumen untuk mulai dianalisis.</h1>
            <p>
                Lengkapi metadata agar hasil analisis lebih terarah. File disimpan secara privat
                dan hanya dapat diakses oleh akun Anda.
            </p>

            <ol class="upload-guide">
                <li><strong>01</strong><span>Isi identitas dan konteks dokumen.</span></li>
                <li><strong>02</strong><span>Pilih PDF atau DOCX maksimal 10 MB.</span></li>
                <li><strong>03</strong><span>Teks diekstrak untuk proses analisis berikutnya.</span></li>
            </ol>
        </aside>

        <div class="upload-card">
            <div class="form-heading">
                <p class="eyebrow">Document details</p>
                <h2>Upload dokumen</h2>
                <p>Kolom bertanda wajib harus diisi sebelum dokumen dikirim.</p>
            </div>

            <div id="uploadAlert" class="form-alert hidden" role="alert"></div>

            <form id="uploadDocumentForm" class="document-form" enctype="multipart/form-data" novalidate>
                <label class="field-wide">
                    <span>Judul dokumen <b>*</b></span>
                    <input type="text" name="title" maxlength="255" placeholder="Contoh: Analisis Sentimen Ulasan Akademik" required>
                </label>

                <label>
                    <span>Jenis dokumen <b>*</b></span>
                    <select name="document_type_id" id="documentTypeSelect" required disabled>
                        <option value="">Memuat jenis dokumen...</option>
                    </select>
                </label>

                <label>
                    <span>Topik / bidang</span>
                    <input type="text" name="topic" maxlength="255" placeholder="Contoh: Natural Language Processing">
                </label>

                <label class="field-wide">
                    <span>Kata kunci</span>
                    <input type="text" name="keywords" placeholder="Contoh: AI, analisis sentimen, artikel ilmiah">
                </label>

                <label class="field-wide">
                    <span>Deskripsi singkat</span>
                    <textarea name="description" rows="4" placeholder="Jelaskan tujuan atau konteks dokumen secara singkat."></textarea>
                </label>

                <label class="file-field field-wide" id="fileDropArea">
                    <input type="file" name="file" id="documentFile" accept=".pdf,.docx" required>
                    <span class="file-icon">+</span>
                    <strong id="fileLabel">Pilih file PDF atau DOCX</strong>
                    <small id="fileMeta">Ukuran maksimal 10 MB</small>
                </label>

                <div class="form-actions field-wide">
                    <a href="/dashboard" class="secondary-button button-link">Kembali</a>
                    <button type="submit" class="primary-button">
                        <span class="button-label">Upload dokumen</span>
                        <span class="button-loader hidden">Mengunggah...</span>
                    </button>
                </div>
            </form>
        </div>
    </section>
</main>
@endsection
