@extends('layouts.app', ['title' => 'Daftar | AcadCheck AI'])

@section('page', 'register')

@section('content')
<main class="auth-shell">
    <section class="auth-story register-story">
        <a href="/login" class="brand-mark" aria-label="AcadCheck AI">
            <span class="brand-icon">A</span>
            <span>AcadCheck <strong>AI</strong></span>
        </a>

        <div class="story-copy">
            <p class="eyebrow">Mulai dengan fondasi yang kuat</p>
            <h1>Ubah proses revisi yang rumit menjadi langkah yang jelas.</h1>
            <p>
                Simpan dokumen secara private, dapatkan analisis berbasis rubrik,
                lalu pantau perbaikannya dari satu workspace.
            </p>
        </div>

        <ol class="story-steps">
            <li><span>01</span>Unggah PDF atau DOCX</li>
            <li><span>02</span>Analisis dan revisi</li>
            <li><span>03</span>Siapkan response matrix</li>
        </ol>
    </section>

    <section class="auth-panel">
        <div class="auth-card">
            <div class="auth-heading">
                <p class="eyebrow">Akun baru</p>
                <h2>Buat workspace Anda</h2>
                <p>Daftar untuk mulai mengelola dokumen akademik.</p>
            </div>

            <div id="authAlert" class="auth-alert hidden" role="alert"></div>

            <form id="registerForm" class="auth-form" novalidate>
                <label>
                    <span>Nama lengkap</span>
                    <input type="text" name="name" autocomplete="name" placeholder="Nama Anda" required>
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
                    <span class="button-label">Buat akun</span>
                    <span class="button-loader hidden">Memproses...</span>
                </button>
            </form>

            <p class="auth-switch">
                Sudah punya akun? <a href="/login">Masuk sekarang</a>
            </p>
        </div>
    </section>
</main>
@endsection
