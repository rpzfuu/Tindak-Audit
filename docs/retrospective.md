# Retrospective

## Latar Belakang

TindakAudit dibuat sebagai project kuliah/magang untuk membantu workflow tindak lanjut audit internal. Domainnya cukup nyata: SPI membuat temuan, unit atau bagian memberi tindak lanjut, lalu SPI memvalidasi hasilnya.

Versi awal sudah menunjukkan konsep yang kuat, tetapi belum siap menjadi portfolio karena setup tidak reproducible, beberapa secret masih hardcoded, dan controller terlalu percaya pada data dari client.

## Kondisi Awal

- Migration domain tidak ada, sehingga orang lain tidak bisa setup database TindakAudit dari nol.
- Token Wablas dan nomor debug tersimpan di source.
- Endpoint menerima `is_spi`, `nik`, `created_by`, dan `changed_by` dari request body.
- Operasi multi-tabel belum memakai transaction.
- Upload bukti belum divalidasi di backend.
- README masih bawaan Laravel.
- Build artifact SSR ikut tracked di git.

## Perubahan Portfolio

- Menambahkan migration schema `tindakaudit` dan tabel pendukung Laravel di `public`.
- Memindahkan sumber auth `users` dan referensi HRIS ke database `superapps_dev` agar aplikasi mengikuti boundary sistem asli.
- Menambahkan connection `superapps` untuk membaca `public.users`, `public.user_access`, dan schema `hris`.
- Menyelaraskan migration dengan keputusan dual database: `users` tidak dibuat di TindakAudit, `spi` tanpa timestamps, dan `temuan_history` tanpa `kode_subbagian`.
- Menambahkan kolom portfolio yang memang dibutuhkan aplikasi tetapi tidak ada di dump lama, terutama `rekomendasi.bukti` dan `rekomendasi_history.action`.
- Menambahkan seeder demo untuk SPI, unit usaha, bagian, bidang, temuan, rekomendasi, history, dan notifikasi.
- Memindahkan konfigurasi Wablas ke `.env` dan `config/services.php`.
- Menambahkan mode WhatsApp `log`, `disabled`, dan `wablas`.
- Mengganti sumber identitas aksi penting ke user yang sedang login.
- Menyimpan `created_by` dan `changed_by` sebagai NIK login agar audit trail tidak bergantung pada input client maupun primary key shared database.
- Menambahkan gate login via `public.user_access.aplikasi=tindakaudit`.
- Menambahkan bootstrap SQL minimal untuk schema superapps yang dibutuhkan fresh clone, tanpa membawa dump data asli.
- Menambahkan command `php artisan app:install` agar setup lokal menjadi idempotent: env, key, schema superapps, migration, seeder, dan storage link.
- Menambahkan authorization sederhana berbasis SPI, kode unit, dan kode bagian.
- Membungkus operasi mutasi utama dengan `DB::transaction`.
- Memvalidasi upload bukti dan menyimpan file di storage private.
- Menambahkan route download bukti yang tetap melewati auth dan scope akses.
- Mengupdate dependency PHP sampai `composer audit` bersih.
- Mengganti README dengan dokumentasi project, instalasi, akun demo, env vars, dan limitation.
- Token Wablas lama pernah masuk git history dan harus dianggap compromised; rotate token di dashboard Wablas sebelum repository dibuka publik.

## Yang Sengaja Dipertahankan

- Bahasa domain Indonesia tetap dipakai agar jejak project asli terasa natural.
- UI Vue tidak di-rewrite dari nol. Fokus polishing ada di backend, reproducibility, dan dokumentasi.
- Route POST untuk beberapa operasi read masih dipertahankan supaya frontend lama tidak perlu dirombak besar.
- HRIS/auth superapps tidak dijadikan migration lintas-koneksi penuh. Fresh clone memakai bootstrap SQL DDL-only agar tidak konflik dengan tabel superapps asli di environment yang sudah memiliki database shared.

## Next Step

- Jalankan `php artisan migrate --seed` di PostgreSQL lokal atau staging.
- Ambil screenshot Dashboard, Temuan, Validasi, dan Profile.
- Pecah `ApiController` menjadi controller domain jika ingin tahap polish berikutnya.
- Tambahkan policy atau middleware role yang lebih eksplisit.
- Tambahkan smoke test workflow end-to-end setelah database testing siap.
