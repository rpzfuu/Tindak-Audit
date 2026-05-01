# Changelog

Format mengikuti prinsip Keep a Changelog secara ringkas.

## Unreleased

### Added

- Migration domain untuk schema `hris` dan `tindakaudit`.
- Seeder demo untuk akun SPI, unit usaha, bagian, bidang, temuan, rekomendasi, history, dan notifikasi.
- Konfigurasi `DB_SEARCH_PATH` untuk Postgres multi-schema (`tindakaudit,hris,public`).
- Konfigurasi Wablas berbasis env dengan driver `log`, `disabled`, dan `wablas`.
- Route auth untuk download bukti tindak lanjut dari storage private.
- Dokumentasi portfolio di README dan retrospective.

### Changed

- Login dan seed user diselaraskan dengan kolom `nik`.
- Schema user diselaraskan dengan SQL asli: tanpa `name`, `email`, dan `email_verified_at`.
- Field audit `created_by` dan `changed_by` disimpan sebagai `users.id`.
- Endpoint utama sekarang memakai identitas user login, bukan `nik`/role dari request body.
- Operasi multi-tabel utama memakai database transaction.
- Upload bukti divalidasi di backend.
- Dependency PHP diperbarui sampai `composer audit` bersih.
- Build artifact SSR dikeluarkan dari tracking git.

### Fixed

- Secret Wablas dan nomor debug hardcoded dihapus dari source.
- Query unread notifikasi memakai boolean `false`, bukan string `'false'`.
- Query validasi memakai `whereIn` agar kondisi OR tidak bocor ke unit lain.
- Autoload PSR-4 service WhatsApp diperbaiki lewat nama file `WhatsAppService.php`.
