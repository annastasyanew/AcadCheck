# AI Journal Recommendation

Fitur AI Journal Matchmaking membantu pengguna menemukan jurnal yang relevan untuk artikel ilmiah. Admin terlebih dahulu mengimpor dataset jurnal SINTA dalam bentuk CSV. Data jurnal yang telah diverifikasi dan diaktifkan akan digunakan sebagai basis rekomendasi.

AI kemudian mencocokkan isi artikel pengguna dengan scope, subject area, keyword, dan level SINTA jurnal yang tersedia. Sistem menampilkan rekomendasi jurnal, fit score, alasan kecocokan, risiko submit, serta saran perbaikan sebelum artikel dikirim.

Keunggulan fitur ini adalah AI tidak mencari jurnal secara bebas dari internet, tetapi memilih dari database jurnal terkurasi yang dikelola admin. Pendekatan ini mengurangi risiko rekomendasi jurnal yang tidak valid dan membuat hasil rekomendasi lebih terkontrol.

## Checklist

- Tabel `journals` tersedia.
- Admin bisa import CSV jurnal.
- Data jurnal tampil di halaman admin.
- Admin bisa aktif/nonaktif jurnal.
- Tabel `journal_recommendations` tersedia.
- Endpoint `POST /api/documents/{document}/journal-recommendations` berjalan.
- Endpoint `GET /api/documents/{document}/journal-recommendations` berjalan.
- Tombol rekomendasi jurnal muncul hanya di detail artikel.
- Hasil rekomendasi tampil di frontend.
- Rekomendasi hanya memakai jurnal aktif dan verified.
- UI menampilkan disclaimer bahwa sistem tidak menjamin artikel diterima.
