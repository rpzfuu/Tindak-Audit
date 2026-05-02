# Changelog

Format mengikuti prinsip Keep a Changelog secara ringkas.

## Unreleased

### Added

- Migration domain untuk schema `tindakaudit`.
- Seeder demo untuk akun SPI, unit usaha, bagian, bidang, temuan, rekomendasi, history, notifikasi, dan akses `user_access`.
- Konfigurasi `superapps` connection untuk auth, access gate, dan HRIS shared database.
- Konfigurasi `DB_SEARCH_PATH` untuk Postgres multi-schema (`public,tindakaudit`).
- Konfigurasi Wablas berbasis env dengan driver `log`, `disabled`, dan `wablas`.
- Route auth untuk download bukti tindak lanjut dari storage private.
- Command `php artisan app:install` untuk fresh-clone bootstrap yang idempotent.
- Bootstrap SQL minimal `database/sql/superapps_bootstrap.sql` untuk schema HRIS/auth superapps tanpa data asli.
- Workflow CI `fresh-install` untuk verifikasi install dari nol.
- Dokumentasi portfolio di README dan retrospective.

### Changed

- Login dan seed user dipindahkan ke `superapps_dev.public.users`.
- Schema user tidak lagi dibuat di database TindakAudit.
- Field audit `created_by` dan `changed_by` disimpan sebagai NIK string.
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
