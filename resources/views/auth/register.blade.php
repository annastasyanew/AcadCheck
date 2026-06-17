@extends('layouts.app', ['title' => 'Daftar | AcadCheck AI'])

@section('page', 'register')

@section('content')
<main class="auth-shell">
    <section class="auth-story register-story">
        <a href="/login" class="brand-mark" aria-label="AcadCheck AI">
            <span class="brand-icon">A</span>
            <span>AcadCheck <strong>AI</strong><small>Academic Document Assistant</small></span>
        </a>

        <div class="story-copy">
            <p class="eyebrow">Academic Document Workspace</p>
            <h1>Mulai kelola dokumen akademik dengan lebih rapi.</h1>
            <p>
                Unggah artikel, proposal, atau laporan. Dapatkan analisis berbasis rubrik,
                rekomendasi revisi, dan riwayat perkembangan dokumen.
            </p>
        </div>

        <div class="auth-visual" aria-hidden="true">
            <div class="document-stack">
                <div class="document-sheet document-sheet-back"></div>
                <div class="document-sheet register-sheet">
                    <span></span>
                    <span></span>
                    <span></span>
                    <strong>01</strong>
                </div>
            </div>
            <div class="auth-feature-list single-feature">
                <div><strong>Untuk kebutuhan akademik</strong><span>Sistem ini membantu pengguna memahami kelemahan dokumen dan menyusun revisi secara lebih terstruktur.</span></div>
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
                <p class="eyebrow">Buat akun baru</p>
                <h2>Daftar Akun</h2>
                <p>Buat akun untuk mulai mengelola dokumen akademik.</p>
            </div>

            <div id="authAlert" class="auth-alert hidden" role="alert"></div>

            <form id="registerForm" class="auth-form" novalidate>
                <label>
                    <span>Nama lengkap</span>
                    <input type="text" name="name" autocomplete="name" placeholder="Masukkan nama lengkap" required>
                </label>

                <label>
                    <span>Email</span>
                    <input type="email" name="email" autocomplete="email" placeholder="nama@kampus.ac.id" required>
                </label>

                <label>
                    <span>Password</span>
                    <input type="password" name="password" autocomplete="new-password" placeholder="Minimal 8 karakter" minlength="8" required>
                </label>

                <button type="submit" class="primary-button">
                    <span class="button-label">Daftar</span>
                    <span class="button-loader hidden">Memproses...</span>
                </button>
            </form>

            <p class="auth-switch">
                Sudah punya akun? <a href="/login">Masuk</a>
            </p>
        </div>

        <p class="auth-footnote">
            Data dokumen pengguna dikelola melalui sistem backend dan akses berbasis autentikasi.
        </p>
    </section>
</main>
@endsection
