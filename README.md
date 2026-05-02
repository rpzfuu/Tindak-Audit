# TindakAudit

**Bahasa:** Indonesia | [English](#english)

[![Fresh Install](https://github.com/rpzfuu/TA/actions/workflows/fresh-install.yml/badge.svg)](https://github.com/rpzfuu/TA/actions/workflows/fresh-install.yml)

TindakAudit adalah aplikasi web untuk memantau tindak lanjut audit internal. SPI membuat temuan dan rekomendasi, unit atau bagian terkait mengunggah bukti tindak lanjut, lalu SPI melakukan validasi sampai temuan selesai.

Aplikasi ini memakai sumber identitas yang terpisah dari database domain: autentikasi user dan data HRIS dibaca dari database `superapps_dev`, sedangkan data audit disimpan di database `TindakAudit`.

## Fitur Utama

- Login berbasis NIK dan password dari `superapps_dev.public.users`.
- Gate akses aplikasi via `superapps_dev.public.user_access` dengan `aplikasi=tindakaudit`.
- Role SPI berdasarkan tabel `TindakAudit.tindakaudit.spi`.
- Input temuan audit, rekomendasi, dan status draft.
- Kirim temuan ke unit usaha atau bagian kantor direksi.
- Upload bukti tindak lanjut dengan validasi PDF/JPG/PNG maksimal 5 MB.
- Validasi tindak lanjut oleh SPI.
- Audit trail temuan dan rekomendasi pada setiap transisi status.
- Notifikasi in-app dengan counter unread.
- Integrasi WhatsApp via Wablas, dengan mode `log`, `disabled`, dan `wablas`.
- Seeder demo untuk akun, HRIS, akses aplikasi, dan data workflow.

## Stack

- Laravel 11
- PHP 8.2
- PostgreSQL
- Vue 3, TypeScript, Inertia.js
- Tailwind CSS, DaisyUI
- Laravel Sanctum
- Wablas WhatsApp gateway

Composer dikunci dengan `config.platform.php = 8.2.0` agar lock file stabil untuk runtime Laravel 11. PHP yang lebih baru dapat memunculkan deprecation warning dari dependency lama, tetapi target runtime project tetap PHP 8.2.

## Arsitektur Database

TindakAudit memakai dua koneksi PostgreSQL:

| Koneksi | Database | Search Path | Tanggung Jawab |
| --- | --- | --- | --- |
| `pgsql` | `TindakAudit` | `public,tindakaudit` | Tabel domain audit dan tabel pendukung Laravel |
| `superapps` | `superapps_dev` | `public,hris` | Auth user, gate akses aplikasi, dan data HRIS |

Tabel yang dimiliki `TindakAudit`:

- `tindakaudit.bidang`
- `tindakaudit.spi`
- `tindakaudit.temuan`
- `tindakaudit.rekomendasi`
- `tindakaudit.temuan_history`
- `tindakaudit.rekomendasi_history`
- `tindakaudit.notifikasi`
- `public.sessions`, `cache`, `jobs`, `migrations`, `password_reset_tokens`

Tabel yang dibaca dari `superapps_dev`:

- `public.users`
- `public.user_access`
- `hris.unit_usaha`
- `hris.bagian`
- `hris.sub_bagian`
- `hris.karyawan`
- `hris.holiday`

Schema lain di `superapps_dev`, seperti `espp`, `monitoring_pr`, `dashboard`, dan `material_management`, tidak disentuh oleh aplikasi ini.

Kolom audit `temuan.created_by` dan `temuan_history.changed_by` menyimpan NIK string, bukan `users.id`, agar riwayat audit tetap stabil meskipun user berada di database shared.

## Prasyarat

- PHP 8.2 dengan extension `pdo_pgsql`, `mbstring`, dan `openssl`
- Composer 2
- Node.js 18+
- PostgreSQL 14+
- Dua database lokal:
  - `TindakAudit`
  - `superapps_dev`

Database `TindakAudit` dibuat kosong. Database `superapps_dev` juga boleh kosong untuk fresh clone; `php artisan app:install` akan membuat schema HRIS/auth minimal dari `database/sql/superapps_bootstrap.sql`.

## Setup Fresh Clone

```bash
git clone https://github.com/rpzfuu/TA.git
cd TA
composer install
npm install
npm run build
createdb -U postgres TindakAudit
createdb -U postgres superapps_dev
php -r "file_exists('.env') || copy('.env.example', '.env');"
```

Isi kredensial PostgreSQL di `.env`:

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=TindakAudit
DB_USERNAME=postgres
DB_PASSWORD=your_password
DB_SEARCH_PATH=public,tindakaudit
DB_MIGRATIONS_TABLE=public.migrations

SUPERAPPS_DB_HOST=127.0.0.1
SUPERAPPS_DB_PORT=5432
SUPERAPPS_DB_DATABASE=superapps_dev
SUPERAPPS_DB_USERNAME=postgres
SUPERAPPS_DB_PASSWORD="${DB_PASSWORD}"
SUPERAPPS_DB_SEARCH_PATH=public,hris

TINDAKAUDIT_APP_CODE=tindakaudit
TINDAKAUDIT_REAL_ACCESS_NIKS=
```

Jalankan installer aplikasi:

```bash
php artisan app:install
php artisan serve
```

Aplikasi berjalan di:

```text
http://127.0.0.1:8000/login
```

`app:install` bersifat idempotent dan dapat dijalankan ulang. Command ini membuat `.env` bila belum ada, memastikan `APP_KEY`, mengecek koneksi `pgsql` dan `superapps`, membuat schema superapps minimal bila kosong, menjalankan migration, menjalankan seeder, membuat storage link, lalu menampilkan kredensial demo.

Seeder mengisi data demo di `superapps_dev` memakai `updateOrInsert`, sehingga bersifat additive untuk NIK demo dan tidak menghapus data shared. Jika PostgreSQL CLI seperti `createdb` belum tersedia di PATH, buat dua database kosong tersebut melalui pgAdmin atau tool database lain.

## Akun Demo

Semua akun demo memakai password:

```text
password
```

| Role | NIK | Keterangan |
| --- | --- | --- |
| SPI | `19990001` | Input temuan, kirim temuan, validasi bukti |
| SPI Reviewer | `19990002` | Akun SPI kedua untuk demo |
| Unit Usaha | `19990003` | Proses temuan dan input tindak lanjut unit |
| Unit Usaha | `19990004` | Unit lain untuk uji authorization |
| Bagian Kantor Direksi | `19990005` | Tindak lanjut bagian Tanaman |
| Bagian Kantor Direksi | `19990006` | Bagian lain untuk uji authorization |
| Bagian Kantor Direksi | `19990007` | Tindak lanjut bagian SDM |
| Bagian Kantor Direksi | `19990008` | Akun demo tambahan |

Seeder membuat NIK `19990001` sampai `19990008` di:

- `superapps_dev.hris.karyawan`
- `superapps_dev.public.users`
- `superapps_dev.public.user_access`

Untuk NIK real, pastikan NIK sudah ada di `hris.karyawan` dan `public.users`, lalu tambahkan entry `public.user_access` untuk `aplikasi=tindakaudit`. Alternatifnya, isi `TINDAKAUDIT_REAL_ACCESS_NIKS` dengan daftar NIK dipisahkan koma sebelum menjalankan seeder.

## Alur Login

1. User login memakai NIK dan password.
2. Laravel membaca user dari `superapps_dev.public.users`.
3. Aplikasi mengecek `superapps_dev.public.user_access` dengan `aplikasi=tindakaudit`.
4. User yang terdaftar sebagai SPI di `tindakaudit.spi` dapat melewati gate `user_access`.
5. Setelah login, aplikasi memuat profil HRIS dari `superapps_dev.hris.karyawan`.

User valid yang belum memiliki akses aplikasi akan ditolak dengan pesan:

```text
Akun Anda belum terdaftar untuk aplikasi TindakAudit.
```

## Workflow Utama

1. SPI login.
2. SPI membuat temuan dan rekomendasi.
3. SPI mengirim temuan ke unit atau bagian terkait.
4. Unit atau bagian login dan melihat temuan masuk.
5. Unit atau bagian mengunggah bukti tindak lanjut.
6. SPI login kembali dan melakukan validasi.
7. Unit atau bagian melakukan konfirmasi akhir jika tindak lanjut sudah sesuai.

Download bukti melalui `/api/bukti/{rekomendasi}` tetap melewati auth dan scope akses. User di luar unit atau bagian terkait akan mendapatkan `403 Forbidden`.

## Perintah Pengembangan

```bash
php artisan serve
npm run dev
```

Build production:

```bash
npm run build
```

Format PHP:

```bash
vendor/bin/pint --dirty
```

Test:

```bash
php artisan test
```

`phpunit.xml` memakai:

```xml
<env name="DB_DATABASE" value="TindakAudit_test"/>
<env name="SUPERAPPS_DB_DATABASE" value="superapps_dev_test"/>
```

`RefreshDatabase` hanya me-refresh database `TindakAudit_test`. Database `superapps_dev_test` perlu memiliki schema `public` dan `hris`; seeder akan mengisi data demo yang diperlukan.

Untuk menyiapkan database test dari nol:

```bash
createdb -U postgres TindakAudit_test
createdb -U postgres superapps_dev_test
DB_DATABASE=TindakAudit_test SUPERAPPS_DB_DATABASE=superapps_dev_test php artisan app:install
php artisan test
```

Di PowerShell, set env sementara sebelum menjalankan installer:

```powershell
$env:DB_DATABASE='TindakAudit_test'
$env:SUPERAPPS_DB_DATABASE='superapps_dev_test'
php artisan app:install
php artisan test
```

Jika hanya ingin menyiapkan schema tanpa data demo, jalankan `php artisan app:install --no-seed`, lalu `php artisan db:seed` saat data demo dibutuhkan.

## Environment WhatsApp

Default demo tidak mengirim pesan sungguhan.

```env
WABLAS_DRIVER=log
WABLAS_SERVER=https://pati.wablas.com
WABLAS_TOKEN=
WABLAS_DEBUG_PHONE=
```

Nilai `WABLAS_DRIVER`:

- `log`: pesan dicatat di log Laravel.
- `disabled`: integrasi dimatikan total.
- `wablas`: pesan dikirim melalui Wablas memakai `WABLAS_TOKEN`.

Jika token Wablas pernah masuk git history, token lama harus dianggap bocor dan perlu di-rotate dari dashboard Wablas.

## Struktur Project

- `app/Http/Controllers/ApiController.php`: endpoint workflow audit, notifikasi, dan authorization.
- `app/Http/Requests/Auth/LoginRequest.php`: autentikasi dan gate `user_access`.
- `app/Console/Commands/AppInstall.php`: installer fresh clone idempotent.
- `app/Models/User.php`: model auth dari `superapps.public.users`.
- `app/Models/HRIS`: model HRIS dari connection `superapps`.
- `app/Models/TindakAudit`: model domain audit dari connection `pgsql`.
- `database/migrations`: schema TindakAudit dan tabel pendukung Laravel.
- `database/sql/superapps_bootstrap.sql`: DDL minimal superapps untuk fresh clone.
- `database/seeders/DatabaseSeeder.php`: seed demo untuk superapps dan TindakAudit.
- `resources/js/Pages`: halaman Inertia/Vue.
- `resources/js/Components`: komponen UI workflow.
- `docs/architecture.md`: ringkasan arsitektur dual database.
- `docs/retrospective.md`: catatan keputusan teknis dan polishing.

## Verifikasi Manual

Checklist smoke test:

- Login `19990001 / password` masuk dashboard SPI.
- Refresh halaman setelah login tetap berada di dashboard.
- Login `19990003 / password` masuk dashboard unit.
- NIK valid tanpa `user_access` ditolak dengan pesan belum terdaftar.
- SPI dapat input temuan dan mengirim temuan.
- Unit terkait dapat melihat temuan dan upload bukti.
- SPI dapat memvalidasi tindak lanjut.
- User di luar unit terkait mendapatkan `403` saat membuka `/api/bukti/{rekomendasi}`.

## Catatan Security

- Identitas aksi penting selalu diambil dari user login, bukan request body.
- Field audit menyimpan NIK login.
- Endpoint mutasi SPI dibatasi ke user yang terdaftar di `tindakaudit.spi`.
- Akses temuan, notifikasi, dan bukti dibatasi berdasarkan role, unit, atau bagian.
- Upload bukti divalidasi dan disimpan di storage private.
- Operasi multi-tabel utama memakai database transaction.
- Token Wablas harus disimpan di env dan di-rotate jika pernah bocor.

## Known Limitations

- `ApiController` masih besar dan dapat dipecah menjadi controller domain pada tahap refactor berikutnya.
- Beberapa endpoint read masih memakai POST untuk menjaga kompatibilitas frontend lama.
- Test suite membutuhkan PostgreSQL dan schema superapps test.
- Runtime PHP di atas 8.2 dapat memunculkan deprecation warning dari dependency testing lama.
- Build frontend dapat menampilkan warning chunk size dan Browserslist outdated; build tetap valid.

---

<a id="english"></a>

# TindakAudit

**Language:** [Indonesia](#tindakaudit) | English

TindakAudit is a web application for monitoring internal audit follow-up. SPI creates findings and recommendations, the related business unit or head-office department uploads follow-up evidence, and SPI validates the result until the finding is completed.

The application uses a separate identity source from the domain database: authentication users and HRIS data are read from `superapps_dev`, while audit data is stored in `TindakAudit`.

## Key Features

- NIK and password login from `superapps_dev.public.users`.
- Application access gate via `superapps_dev.public.user_access` with `aplikasi=tindakaudit`.
- SPI role based on `TindakAudit.tindakaudit.spi`.
- Audit finding, recommendation, and draft status management.
- Send findings to business units or head-office departments.
- Upload follow-up evidence with PDF/JPG/PNG validation, maximum 5 MB.
- SPI follow-up validation.
- Audit trail for finding and recommendation status transitions.
- In-app notifications with unread counter.
- Wablas WhatsApp integration with `log`, `disabled`, and `wablas` modes.
- Demo seeder for accounts, HRIS, application access, and workflow data.

## Stack

- Laravel 11
- PHP 8.2
- PostgreSQL
- Vue 3, TypeScript, Inertia.js
- Tailwind CSS, DaisyUI
- Laravel Sanctum
- Wablas WhatsApp gateway

Composer is locked with `config.platform.php = 8.2.0` to keep the Laravel 11 lock file stable. Newer PHP versions may show deprecation warnings from older dependencies, but the intended runtime is PHP 8.2.

## Database Architecture

TindakAudit uses two PostgreSQL connections:

| Connection | Database | Search Path | Responsibility |
| --- | --- | --- | --- |
| `pgsql` | `TindakAudit` | `public,tindakaudit` | Audit domain tables and Laravel support tables |
| `superapps` | `superapps_dev` | `public,hris` | Auth users, application access gate, and HRIS data |

Tables owned by `TindakAudit`:

- `tindakaudit.bidang`
- `tindakaudit.spi`
- `tindakaudit.temuan`
- `tindakaudit.rekomendasi`
- `tindakaudit.temuan_history`
- `tindakaudit.rekomendasi_history`
- `tindakaudit.notifikasi`
- `public.sessions`, `cache`, `jobs`, `migrations`, `password_reset_tokens`

Tables read from `superapps_dev`:

- `public.users`
- `public.user_access`
- `hris.unit_usaha`
- `hris.bagian`
- `hris.sub_bagian`
- `hris.karyawan`
- `hris.holiday`

Other schemas in `superapps_dev`, such as `espp`, `monitoring_pr`, `dashboard`, and `material_management`, are not modified by this application.

Audit columns `temuan.created_by` and `temuan_history.changed_by` store the actor NIK as a string instead of `users.id`, keeping audit history stable even though users live in a shared database.

## Requirements

- PHP 8.2 with `pdo_pgsql`, `mbstring`, and `openssl` extensions
- Composer 2
- Node.js 18+
- PostgreSQL 14+
- Two local databases:
  - `TindakAudit`
  - `superapps_dev`

`TindakAudit` starts empty. `superapps_dev` may also start empty for a fresh clone; `php artisan app:install` creates the minimal HRIS/auth schema from `database/sql/superapps_bootstrap.sql`.

## Fresh Clone Setup

```bash
git clone https://github.com/rpzfuu/TA.git
cd TA
composer install
npm install
npm run build
createdb -U postgres TindakAudit
createdb -U postgres superapps_dev
php -r "file_exists('.env') || copy('.env.example', '.env');"
```

Fill PostgreSQL credentials in `.env`:

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=TindakAudit
DB_USERNAME=postgres
DB_PASSWORD=your_password
DB_SEARCH_PATH=public,tindakaudit
DB_MIGRATIONS_TABLE=public.migrations

SUPERAPPS_DB_HOST=127.0.0.1
SUPERAPPS_DB_PORT=5432
SUPERAPPS_DB_DATABASE=superapps_dev
SUPERAPPS_DB_USERNAME=postgres
SUPERAPPS_DB_PASSWORD="${DB_PASSWORD}"
SUPERAPPS_DB_SEARCH_PATH=public,hris

TINDAKAUDIT_APP_CODE=tindakaudit
TINDAKAUDIT_REAL_ACCESS_NIKS=
```

Run the application installer:

```bash
php artisan app:install
php artisan serve
```

The application runs at:

```text
http://127.0.0.1:8000/login
```

`app:install` is idempotent and can be run repeatedly. It creates `.env` when missing, ensures `APP_KEY`, checks the `pgsql` and `superapps` connections, creates the minimal superapps schema when empty, runs migrations, runs seeders, creates the storage link, and prints demo credentials.

The seeder writes demo data to `superapps_dev` with `updateOrInsert`, so it is additive for demo NIKs and does not delete shared data. If PostgreSQL CLI tools such as `createdb` are not available in PATH, create the two empty databases with pgAdmin or another database tool.

## Demo Accounts

All demo accounts use this password:

```text
password
```

| Role | NIK | Notes |
| --- | --- | --- |
| SPI | `19990001` | Create findings, send findings, validate evidence |
| SPI Reviewer | `19990002` | Second SPI account for demo |
| Business Unit | `19990003` | Process findings and submit follow-up evidence |
| Business Unit | `19990004` | Other unit for authorization tests |
| Head-Office Department | `19990005` | Tanaman department follow-up |
| Head-Office Department | `19990006` | Other department for authorization tests |
| Head-Office Department | `19990007` | SDM department follow-up |
| Head-Office Department | `19990008` | Additional demo account |

The seeder creates NIK `19990001` through `19990008` in:

- `superapps_dev.hris.karyawan`
- `superapps_dev.public.users`
- `superapps_dev.public.user_access`

For real NIKs, make sure the NIK already exists in `hris.karyawan` and `public.users`, then add a `public.user_access` entry for `aplikasi=tindakaudit`. Alternatively, set `TINDAKAUDIT_REAL_ACCESS_NIKS` to a comma-separated NIK list before running the seeder.

## Login Flow

1. User logs in with NIK and password.
2. Laravel reads the user from `superapps_dev.public.users`.
3. The application checks `superapps_dev.public.user_access` with `aplikasi=tindakaudit`.
4. Users registered as SPI in `tindakaudit.spi` can bypass the `user_access` gate.
5. After login, the application loads the HRIS employee profile from `superapps_dev.hris.karyawan`.

A valid user without application access is rejected with:

```text
Akun Anda belum terdaftar untuk aplikasi TindakAudit.
```

## Main Workflow

1. SPI logs in.
2. SPI creates a finding and recommendation.
3. SPI sends the finding to the related unit or department.
4. The related unit or department logs in and sees the incoming finding.
5. The unit or department uploads follow-up evidence.
6. SPI logs back in and validates the follow-up.
7. The unit or department confirms the final validation result when the follow-up is accepted.

Evidence downloads through `/api/bukti/{rekomendasi}` are protected by auth and access scope. Users outside the related unit or department receive `403 Forbidden`.

## Development Commands

```bash
php artisan serve
npm run dev
```

Production build:

```bash
npm run build
```

PHP formatting:

```bash
vendor/bin/pint --dirty
```

Tests:

```bash
php artisan test
```

`phpunit.xml` uses:

```xml
<env name="DB_DATABASE" value="TindakAudit_test"/>
<env name="SUPERAPPS_DB_DATABASE" value="superapps_dev_test"/>
```

`RefreshDatabase` only refreshes `TindakAudit_test`. `superapps_dev_test` must contain the shared `public` and `hris` schemas; the seeder inserts the required demo data.

To prepare test databases from scratch:

```bash
createdb -U postgres TindakAudit_test
createdb -U postgres superapps_dev_test
DB_DATABASE=TindakAudit_test SUPERAPPS_DB_DATABASE=superapps_dev_test php artisan app:install
php artisan test
```

In PowerShell, set temporary env vars before running the installer:

```powershell
$env:DB_DATABASE='TindakAudit_test'
$env:SUPERAPPS_DB_DATABASE='superapps_dev_test'
php artisan app:install
php artisan test
```

To prepare only the schema without demo data, run `php artisan app:install --no-seed`, then run `php artisan db:seed` when demo data is needed.

## WhatsApp Environment

The default demo setup does not send real messages.

```env
WABLAS_DRIVER=log
WABLAS_SERVER=https://pati.wablas.com
WABLAS_TOKEN=
WABLAS_DEBUG_PHONE=
```

`WABLAS_DRIVER` values:

- `log`: messages are written to Laravel logs.
- `disabled`: integration is fully disabled.
- `wablas`: messages are sent through Wablas using `WABLAS_TOKEN`.

If a Wablas token was ever committed to git history, the old token must be treated as leaked and rotated from the Wablas dashboard.

## Project Structure

- `app/Http/Controllers/ApiController.php`: audit workflow, notifications, and authorization endpoints.
- `app/Http/Requests/Auth/LoginRequest.php`: authentication and `user_access` gate.
- `app/Console/Commands/AppInstall.php`: idempotent fresh-clone installer.
- `app/Models/User.php`: auth model from `superapps.public.users`.
- `app/Models/HRIS`: HRIS models from the `superapps` connection.
- `app/Models/TindakAudit`: audit domain models from the `pgsql` connection.
- `database/migrations`: TindakAudit schema and Laravel support tables.
- `database/sql/superapps_bootstrap.sql`: minimal superapps DDL for fresh clones.
- `database/seeders/DatabaseSeeder.php`: demo seed data for superapps and TindakAudit.
- `resources/js/Pages`: Inertia/Vue pages.
- `resources/js/Components`: workflow UI components.
- `docs/architecture.md`: dual database architecture summary.
- `docs/retrospective.md`: technical decisions and polishing notes.

## Manual Verification

Smoke test checklist:

- Login `19990001 / password` reaches the SPI dashboard.
- Refreshing the page after login keeps the session active.
- Login `19990003 / password` reaches the unit dashboard.
- A valid NIK without `user_access` is rejected with the access message.
- SPI can create and send a finding.
- The related unit can see the finding and upload evidence.
- SPI can validate the follow-up.
- A user outside the related unit receives `403` when opening `/api/bukti/{rekomendasi}`.

## Security Notes

- Action identity is always taken from the authenticated user, not from request payloads.
- Audit fields store the authenticated NIK.
- SPI mutations are limited to users registered in `tindakaudit.spi`.
- Finding, notification, and evidence access is scoped by role, unit, or department.
- Evidence uploads are validated and stored in private storage.
- Main multi-table mutations use database transactions.
- Wablas tokens must live in env and be rotated if leaked.

## Known Limitations

- `ApiController` is still large and can be split into domain controllers in a future refactor.
- Some read endpoints still use POST to preserve compatibility with the existing frontend.
- The test suite requires PostgreSQL and a superapps test schema.
- PHP runtimes above 8.2 may show deprecation warnings from older test dependencies.
- Frontend build may show chunk-size and outdated Browserslist warnings; the build remains valid.
