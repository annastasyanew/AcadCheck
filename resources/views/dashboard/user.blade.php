@extends('layouts.app', ['title' => 'Dashboard | AcadCheck AI'])

@section('page', 'user-dashboard')

@section('content')
<main class="destination-shell">
    <div class="destination-card">
        <span class="brand-icon">A</span>
        <p class="eyebrow">User workspace</p>
        <h1>Login berhasil.</h1>
        <p>Mulai alur pemeriksaan dengan mengunggah artikel, proposal, atau laporan akademik Anda.</p>
        <div class="destination-user" id="currentUser"></div>
        <div class="destination-actions">
            <a href="/documents/upload" class="primary-button button-link">Upload dokumen</a>
            <a href="/documents" class="secondary-button button-link">Document library</a>
            <button type="button" class="secondary-button" data-logout>Keluar</button>
        </div>
    </div>
</main>
@endsection
