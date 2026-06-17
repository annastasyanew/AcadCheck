# Final Testing Checklist

Gunakan checklist ini setelah Rubrik AI Admin, API, dan build frontend sudah lolos.

## Validasi Rubrik AI Admin

- Login sebagai admin.
- Buka `/admin/rubrics`.
- Ubah bobot salah satu rubrik.
- Ubah deskripsi salah satu rubrik.
- Nonaktifkan salah satu rubrik.
- Simpan perubahan.
- Refresh halaman.
- Pastikan bobot, deskripsi, dan status aktif/nonaktif tetap tersimpan.
- Jalankan analisis pada dokumen dengan jenis yang sama.
- Pastikan `aspect_scores` mengikuti rubrik aktif terbaru dari database.

## Artikel

- Login sebagai user.
- Upload artikel.
- Jalankan Analisis AI.
- Pastikan aspek analisis sama dengan rubrik aktif artikel.
- Upload revisi.
- Jalankan Analisis AI ulang.
- Buka perbandingan versi.
- Buka Reviewer Mapping.
- Parse komentar reviewer.
- Generate respons penulis.
- Simpan respons penulis.
- Buka Response Matrix.
- Unduh Response Letter.

## Proposal

- Login sebagai user.
- Upload proposal.
- Jalankan Analisis AI.
- Pastikan aspek analisis sama dengan rubrik aktif proposal.
- Upload revisi.
- Jalankan Analisis AI ulang.
- Buka perbandingan versi.
- Pastikan Reviewer Mapping tidak tersedia untuk proposal.

## Laporan

- Login sebagai user.
- Upload laporan.
- Jalankan Analisis AI.
- Pastikan aspek analisis sama dengan rubrik aktif laporan.
- Upload revisi.
- Jalankan Analisis AI ulang.
- Buka perbandingan versi.
- Pastikan Reviewer Mapping tidak tersedia untuk laporan.

## Admin

- Login sebagai admin.
- Buka Dashboard Admin.
- Buka Data User.
- Filter user berdasarkan role/status.
- Aktifkan/nonaktifkan user.
- Buka Data Dokumen.
- Filter dokumen berdasarkan jenis/status.
- Cari dokumen berdasarkan judul, nama user, atau email.
- Buka Rubrik AI.
- Edit rubrik.
- Jalankan analisis dari user dan pastikan efek rubrik berubah.

## Error Handling

- Coba upload file selain PDF/DOCX.
- Coba upload file lebih dari 10 MB.
- Coba analisis dokumen tanpa extracted text.
- Coba endpoint admin memakai token user biasa.
- Coba Reviewer Mapping pada proposal/laporan.
- Coba rubrik aktif dikosongkan untuk satu jenis dokumen, lalu analisis dokumen jenis itu.
