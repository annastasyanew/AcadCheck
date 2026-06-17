@extends('layouts.app', ['title' => 'Masuk | AcadCheck AI'])

@section('page', 'login')

@section('content')
<main class="auth-shell">
    <section class="auth-story">
        <a href="/login" class="brand-mark" aria-label="AcadCheck AI">
            <span class="brand-icon">A</span>
            <span>AcadCheck <strong>AI</strong><small>Academic Document Assistant</small></span>
        </a>

        <div class="story-copy">
            <p class="eyebrow">Academic Document Workspace</p>
            <h1>Analisis dan revisi dokumen akademik secara lebih terarah.</h1>
            <p>
                Bantu pengguna mengecek artikel, proposal, dan laporan melalui analisis AI,
                pelacakan revisi, perbandingan versi, serta pemetaan komentar reviewer.
            </p>
        </div>

        <div class="auth-visual" aria-hidden="true">
            <div class="document-stack">
                <div class="document-sheet document-sheet-back"></div>
                <div class="document-sheet">
                    <span></span>
                    <span></span>
                    <span></span>
                    <strong>88</strong>
                </div>
            </div>
            <div class="auth-feature-list">
                <div><strong>AI Academic Review</strong><span>Menganalisis dokumen berdasarkan rubrik akademik.</span></div>
                <div><strong>Revision Tracking</strong><span>Melacak perkembangan dokumen dari versi awal ke revisi.</span></div>
                <div><strong>Reviewer Mapping</strong><span>Membantu menyusun response matrix artikel ilmiah.</span></div>
            </div>
        </div>
    </section>

    <section class="auth-panel">
        <div class="auth-mobile-brand">
            <span class="brand-icon">A</span>
            <div>
                <strong>AcadCheck AI</strong>
                <span>Academic Document Assistant</span>
            </div>
        </div>

        <div class="auth-card">
            <div class="auth-heading">
                <p class="eyebrow">Selamat datang kembali</p>
                <h2>Masuk Akun</h2>
                <p>Gunakan akun Anda untuk mengakses dokumen dan hasil analisis.</p>
            </div>

            <div id="authAlert" class="auth-alert hidden" role="alert"></div>

            <form id="loginForm" class="auth-form" novalidate>
                <label>
                    <span>Email</span>
                    <input type="email" name="email" autocomplete="email" placeholder="nama@kampus.ac.id" required>
                </label>

                <label>
                    <span>Password</span>
                    <input type="password" name="password" autocomplete="current-password" placeholder="Masukkan password" required>
                </label>

                <button type="submit" class="primary-button">
                    <span class="button-label">Masuk</span>
                    <span class="button-loader hidden">Memproses...</span>
                </button>
            </form>

            <p class="auth-switch">
                Belum punya akun? <a href="/register">Daftar sekarang</a>
            </p>
        </div>

        <p class="auth-footnote">
            AcadCheck AI membantu analisis awal dokumen. Sistem tidak menggantikan dosen, pembimbing, atau reviewer.
        </p>
    </section>
</main>
@endsection
