@extends('layouts.app', ['title' => 'Data User | AcadCheck AI'])

@section('page', 'admin-users')

@section('content')
<main class="workspace-shell admin-workspace">
    <header class="workspace-header">
        <a href="/admin/dashboard" class="brand-mark workspace-brand" aria-label="AcadCheck AI Admin">
            <span class="brand-icon">A</span>
            <span>AcadCheck <strong>AI</strong> Admin</span>
        </a>

        <nav class="workspace-nav" aria-label="Navigasi admin">
            <a href="/admin/dashboard">Dashboard Admin</a>
            <span id="adminUsersIdentity">Admin</span>
            <button type="button" data-logout>Keluar</button>
        </nav>
    </header>

    <section class="admin-shell">
        <div id="adminUsersAlert" class="form-alert hidden" role="alert"></div>

        <section class="admin-hero admin-users-hero">
            <div>
                <p class="eyebrow">User management</p>
                <h1>Data User</h1>
                <p>Kelola akses pengguna dan pantau jumlah dokumen setiap akun AcadCheck AI.</p>
            </div>
            <a href="/admin/dashboard" class="secondary-button button-link">Kembali ke Dashboard</a>
        </section>

        <section class="admin-user-filters">
            <label class="admin-user-search">
                <span>Cari pengguna</span>
                <input id="adminUserSearch" type="search" placeholder="Nama atau email...">
            </label>
            <label>
                <span>Role</span>
                <select id="adminUserRoleFilter">
                    <option value="">Semua role</option>
                    <option value="user">User</option>
                    <option value="admin">Admin</option>
                </select>
            </label>
            <label>
                <span>Status</span>
                <select id="adminUserStatusFilter">
                    <option value="">Semua status</option>
                    <option value="1">Aktif</option>
                    <option value="0">Nonaktif</option>
                </select>
            </label>
            <button type="button" id="resetAdminUserFilters" class="filter-reset">Reset filter</button>
        </section>

        <section id="adminUsersLoading" class="library-state">
            <span class="loading-dot"></span>
            <div>
                <strong>Memuat data user</strong>
                <p>Mengambil akun dan jumlah dokumen...</p>
            </div>
        </section>

        <section id="adminUsersContent" class="hidden">
            <div class="admin-user-meta">
                <p>Menampilkan <strong id="adminUserVisibleCount">0</strong> dari <strong id="adminUserTotalCount">0</strong> user</p>
                <p>Halaman <strong id="adminUserCurrentPage">1</strong> dari <strong id="adminUserLastPage">1</strong></p>
            </div>

            <div class="document-table-wrap">
                <table class="document-table admin-user-table">
                    <thead>
                        <tr>
                            <th>Pengguna</th>
                            <th>Role</th>
                            <th>Jumlah dokumen</th>
                            <th>Status</th>
                            <th>Tanggal daftar</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="adminUserTableBody"></tbody>
                </table>
            </div>

            <div class="admin-pagination">
                <button type="button" id="adminUsersPreviousPage" class="secondary-button">Sebelumnya</button>
                <span id="adminUsersPageLabel">Halaman 1</span>
                <button type="button" id="adminUsersNextPage" class="secondary-button">Berikutnya</button>
            </div>
        </section>
    </section>
</main>
@endsection
