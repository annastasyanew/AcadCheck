@extends('layouts.app', ['title' => 'Masuk | AcadCheck AI'])

@section('page', 'login')

@section('content')
<main class="auth-shell">
    <section class="auth-story">
        <a href="/login" class="brand-mark" aria-label="AcadCheck AI">
            <span class="brand-icon">A</span>
            <span>AcadCheck <strong>AI</strong></span>
        </a>

        <div class="story-copy">
            <p class="eyebrow">Academic revision workspace</p>
            <h1>Dokumen akademik yang lebih tajam, revisi yang lebih terarah.</h1>
            <p>
                Analisis struktur, bandingkan versi, dan siapkan respons reviewer
                dalam satu alur kerja yang rapi.
            </p>
        </div>

        <div class="story-metrics">
            <div><strong>AI</strong><span>Analisis berbasis rubrik</span></div>
            <div><strong>V2</strong><span>Perbandingan antarversi</span></div>
            <div><strong>PDF</strong><span>Response letter siap kirim</span></div>
        </div>
    </section>

    <section class="auth-panel">
        <div class="auth-card">
            <div class="auth-heading">
                <p class="eyebrow">Selamat datang kembali</p>
                <h2>Masuk ke workspace</h2>
                <p>Gunakan akun AcadCheck AI Anda untuk melanjutkan.</p>
            </div>

            <div id="authAlert" class="auth-alert hidden" role="alert"></div>

            <form id="loginForm" class="auth-form" novalidate>
                <label>
                    <span>Email</span>
                    <input type="email" name="email" autocomplete="email" placeholder="nama@kampus.ac.id" required>
                </label>

                <label>
                    <span>Password</span>
                    <input type="password" name="password" autocomplete="current-password" placeholder="Minimal 8 karakter" required>
                </label>

                <button type="submit" class="primary-button">
                    <span class="button-label">Masuk</span>
                    <span class="button-loader hidden">Memproses...</span>
                </button>
            </form>

            <p class="auth-switch">
                Belum punya akun? <a href="/register">Buat akun baru</a>
            </p>
        </div>
    </section>
</main>
@endsection
