# TindakAudit

TindakAudit adalah web app untuk monitoring tindak lanjut audit internal. Workflow utamanya adalah SPI membuat temuan dan rekomendasi, unit atau bagian mengunggah bukti tindak lanjut, lalu SPI melakukan validasi sampai temuan selesai.

Project ini awalnya dibuat sebagai project kuliah/magang, lalu dipoles ulang untuk portfolio dengan fokus pada reproducible setup, security dasar, audit trail, validasi input, dan dokumentasi.

## Stack

- Laravel 11
- PHP 8.2
- PostgreSQL
- Vue 3, TypeScript, Inertia.js
- Tailwind CSS, DaisyUI
- Laravel Sanctum
- Wablas WhatsApp gateway, dengan mode `log`/`disabled` untuk demo

## Fitur

- Login berbasis NIK.
- Role SPI dan non-SPI berdasarkan tabel `tindakaudit.spi`.
- Input temuan audit, rekomendasi, dan status draft.
- Kirim temuan ke unit usaha atau bagian kantor direksi.
- Upload bukti tindak lanjut dengan validasi PDF/JPG/PNG maksimal 5MB.
- Validasi tindak lanjut oleh SPI.
- Audit trail temuan dan rekomendasi pada setiap transisi status.
- Notifikasi in-app dengan counter unread.
- Integrasi WhatsApp yang bisa dimatikan atau diarahkan ke log untuk demo.
- Seeder demo untuk login dan mencoba workflow.

## Prasyarat

- PHP 8.2
- Composer 2
- Node.js 20+
- PostgreSQL 14+

Composer dikunci dengan `config.platform.php = 8.2.0` agar lock file tetap stabil untuk environment Laravel 11. Jika memakai PHP yang lebih baru, beberapa dependency lama mungkin memberi peringatan deprecation, tetapi target runtime yang disarankan tetap PHP 8.2.

## Instalasi

```bash
composer install
npm install --legacy-peer-deps
cp .env.example .env
php artisan key:generate
```

Buat database PostgreSQL, lalu sesuaikan variabel berikut di `.env`:

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=TindakAudit
DB_USERNAME=postgres
DB_PASSWORD=your_password
DB_SEARCH_PATH=tindakaudit,hris,public
DB_MIGRATIONS_TABLE=public.migrations
```

Jalankan migration dan seeder:

```bash
php artisan migrate --seed
npm run build
php artisan serve
```

## Akun Demo

Semua akun demo memakai password:

```text
password
```

| Role | NIK | Keterangan |
| --- | --- | --- |
| SPI | `19990001` | Input temuan, kirim temuan, validasi bukti |
| SPI Reviewer | `19990002` | Akun SPI kedua untuk validasi/demo |
| Unit Usaha | `19990003` | Proses temuan dan input tindak lanjut unit |
| Bagian Kantor Direksi | `19990005` | Proses temuan dan input tindak lanjut bagian Tanaman |
| Bagian Kantor Direksi | `19990007` | Proses temuan dan input tindak lanjut bagian SDM |

## Environment WhatsApp

Default demo tidak mengirim pesan sungguhan.

```env
WABLAS_DRIVER=log
WABLAS_SERVER=https://pati.wablas.com
WABLAS_TOKEN=
WABLAS_DEBUG_PHONE=
```

Nilai `WABLAS_DRIVER`:

- `log`: pesan dicatat di log Laravel, cocok untuk demo.
- `disabled`: integrasi dimatikan total.
- `wablas`: kirim ke Wablas memakai `WABLAS_TOKEN`.

Jika token Wablas pernah masuk git history, token lama harus di-rotate dari dashboard Wablas. Menghapus token dari source tidak membuat token lama kembali aman.

## Struktur Penting

- `app/Http/Controllers/ApiController.php`: endpoint workflow audit dan notifikasi.
- `app/Models/TindakAudit`: model domain audit.
- `app/Models/HRIS`: model mock HRIS lokal untuk demo mandiri.
- `database/migrations`: schema `hris` dan `tindakaudit`.
- `database/seeders/DatabaseSeeder.php`: data demo portfolio.
- `resources/js/Pages` dan `resources/js/Components`: UI Inertia/Vue.
- `docs/retrospective.md`: catatan polishing dan keputusan teknis.

## Catatan Security

Perbaikan portfolio yang sudah diterapkan:

- Token dan nomor debug WhatsApp dipindahkan ke env/config.
- Identitas user tidak lagi dipercaya dari request body untuk aksi penting.
- Schema `users` diselaraskan dengan SQL asli: login NIK-only tanpa kolom `name`/`email`.
- Kolom audit `created_by` dan `changed_by` memakai `users.id`, bukan NIK dari client.
- Endpoint mutasi SPI dibatasi ke user yang terdaftar di `tindakaudit.spi`.
- Akses temuan/notifikasi dibatasi berdasarkan role, unit, atau bagian user.
- Operasi multi-tabel utama memakai `DB::transaction`.
- Upload bukti divalidasi dan disimpan di storage private, diakses melalui route auth.
- Dependency PHP diaudit dan diperbarui sampai `composer audit` bersih.
- Runtime npm production audit bersih via `npm audit --omit=dev`.

## Known Limitations

- Controller API masih besar. Refactor berikutnya yang masuk akal adalah memecahnya menjadi controller domain tanpa mengubah kontrak frontend.
- Query API masih mempertahankan beberapa route POST untuk read agar frontend lama tetap kompatibel.
- Test suite membutuhkan PostgreSQL dengan schema `hris` dan `tindakaudit`; `php artisan migrate --seed` dan `php artisan test` belum bisa diverifikasi di mesin tanpa kredensial DB lokal.
- Environment ini memakai PHP 8.5, sehingga beberapa dependency Laravel/Pest menampilkan deprecation warning. Target runtime project tetap PHP 8.2 sesuai `composer.json`.
- `npm audit` penuh masih menyisakan low/moderate advisory pada dev tooling lama (`vite/esbuild`) dan type package `vue-select`. Runtime production audit (`npm audit --omit=dev`) sudah bersih.
- Screenshot belum disertakan di repo ini. Ambil screenshot setelah `php artisan migrate --seed` berhasil: Dashboard, Temuan, Validasi, Profile.

## Verification

Perintah yang dipakai saat polishing:

```bash
composer audit
composer validate --strict
vendor/bin/pint --dirty
npm audit --omit=dev
npm run build
php artisan route:list
```

`php artisan migrate --seed` dan `php artisan test` belum dijalankan sampai selesai di mesin ini karena PostgreSQL lokal meminta password untuk user `postgres`.
