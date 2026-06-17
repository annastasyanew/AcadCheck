# AcadCheck AI REST API

Base URL lokal:

```text
http://127.0.0.1:8000
```

Header untuk endpoint yang membutuhkan login:

```http
Accept: application/json
Authorization: Bearer <token>
```

## Auth

### Login

```http
POST /api/login
Content-Type: application/json
```

```json
{
  "email": "user@example.com",
  "password": "password"
}
```

Response sukses berisi `token` dan `user`. Simpan token sebagai variable Postman, misalnya `token`.

### Register

```http
POST /api/register
Content-Type: application/json
```

```json
{
  "name": "User Demo",
  "email": "demo@example.com",
  "password": "password",
  "password_confirmation": "password"
}
```

### Current User

```http
GET /api/me
```

### Logout

```http
POST /api/logout
```

## Documents

### Document Types

```http
GET /api/document-types
```

Gunakan `id` dari response sebagai `document_type_id` saat upload dokumen.

### List Documents

```http
GET /api/documents
```

### Upload Document

```http
POST /api/documents
Content-Type: multipart/form-data
```

Fields:

| Key | Type | Required |
| --- | --- | --- |
| document_type_id | text | yes |
| title | text | yes |
| topic | text | no |
| keywords | text | no |
| description | text | no |
| file | file | yes, PDF/DOCX max 10 MB |

Simpan `data.document.id` sebagai `document_id`.

### Document Detail

```http
GET /api/documents/{document_id}
```

## AI Analysis

### Run Analysis

```http
POST /api/documents/{document_id}/analyze
```

Response menyimpan hasil analisis dan `aspect_scores`. Aspek yang muncul harus sama dengan rubrik aktif untuk jenis dokumen tersebut.

### Latest Analysis

```http
GET /api/documents/{document_id}/analysis
```

## Versions

### List Versions

```http
GET /api/documents/{document_id}/versions
```

### Upload Revision

```http
POST /api/documents/{document_id}/versions
Content-Type: multipart/form-data
```

Fields:

| Key | Type | Required |
| --- | --- | --- |
| file | file | yes, PDF/DOCX max 10 MB |
| revision_note | text | no |

## Comparison

```http
GET /api/documents/{document_id}/comparison
```

## Reviewer Mapping

Reviewer Mapping hanya tersedia untuk dokumen dengan jenis `article`.

### List Reviewer Comments

```http
GET /api/articles/{document_id}/reviewer-comments
```

### Add Reviewer Comment

```http
POST /api/articles/{document_id}/reviewer-comments
Content-Type: application/json
```

```json
{
  "reviewer_label": "Reviewer 1",
  "comment_number": 1,
  "original_comment": "Metode perlu diperjelas.",
  "related_section": "Metode",
  "priority": "major"
}
```

### Parse Reviewer Comments With AI

```http
POST /api/articles/{document_id}/reviewer-comments/parse
Content-Type: application/json
```

```json
{
  "reviewer_text": "Reviewer 1: Metode perlu diperjelas.",
  "save_to_database": true
}
```

### Generate Author Response

```http
POST /api/reviewer-comments/{reviewer_comment_id}/generate-response
Content-Type: application/json
```

```json
{
  "revision_made": "Metode penelitian telah diperjelas.",
  "revision_location": "Halaman 4",
  "save_to_database": false
}
```

### Store Author Response

```http
POST /api/reviewer-comments/{reviewer_comment_id}/responses
Content-Type: application/json
```

```json
{
  "author_response": "Terima kasih. Metode penelitian telah kami perjelas.",
  "revision_made": "Metode diperjelas.",
  "revision_location": "Halaman 4",
  "revised_version_id": null
}
```

### Response Matrix

```http
GET /api/articles/{document_id}/response-matrix
```

### Response Letter

```http
GET /api/articles/{document_id}/response-letter
```

## Admin

Endpoint admin membutuhkan token user dengan role `admin`.

### Admin Dashboard

```http
GET /api/admin/dashboard
```

### Admin Users

```http
GET /api/admin/users?search=&role=&is_active=&per_page=15
```

### Update User Status

```http
PUT /api/admin/users/{user_id}/status
Content-Type: application/json
```

```json
{
  "is_active": false
}
```

### Admin Documents

```http
GET /api/admin/documents?search=&document_type=&status=&per_page=15
```

## Rubrics

### List Rubrics

```http
GET /api/rubrics?document_type=article
```

`document_type` optional. Nilai umum: `article`, `proposal`, `report`.

### Update Rubric

```http
PUT /api/admin/rubrics/{rubric_id}
Content-Type: application/json
```

```json
{
  "aspect_name": "Referensi",
  "weight": 5,
  "description": "Referensi relevan, mutakhir, dan konsisten.",
  "is_active": true
}
```

## Validasi Rubrik Ke AI

1. Login sebagai admin.
2. Buka `GET /api/rubrics?document_type=article`.
3. Pilih rubrik artikel, misalnya `Referensi`.
4. Kirim `PUT /api/admin/rubrics/{rubric_id}` dengan `is_active: false`.
5. Login sebagai user.
6. Jalankan `POST /api/documents/{document_id}/analyze` pada dokumen artikel.
7. Pastikan `data.aspect_scores` tidak memuat aspek `Referensi`.
8. Aktifkan kembali rubrik jika dibutuhkan.
