@extends('layouts.app', ['title' => 'Admin Dashboard | AcadCheck AI'])

@section('page', 'admin-dashboard')

@section('content')
<main class="destination-shell admin-destination">
    <div class="destination-card">
        <span class="brand-icon">A</span>
        <p class="eyebrow">Admin workspace</p>
        <h1>Selamat datang, Admin.</h1>
        <p>Redirect berbasis role berhasil. Dashboard admin lengkap akan dibangun setelah frontend dashboard user.</p>
        <div class="destination-user" id="currentUser"></div>
        <button type="button" class="secondary-button" data-logout>Keluar</button>
    </div>
</main>
@endsection
