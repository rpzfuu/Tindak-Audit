# Architecture

## Dual Database Layout

```text
Laravel app
  |
  |-- pgsql connection
  |     database: TindakAudit
  |     search_path: public,tindakaudit
  |     owns:
  |       - public.sessions, cache, jobs, migrations, password_reset_tokens
  |       - tindakaudit.bidang, spi, temuan, rekomendasi
  |       - tindakaudit.*_history, notifikasi
  |
  |-- superapps connection
        database: superapps_dev
        search_path: public,hris
        reads:
          - public.users
          - public.user_access
          - hris.unit_usaha, bagian, sub_bagian, karyawan, holiday
        demo seed only:
          - NIK 19990001-19990008
          - user_access.aplikasi=tindakaudit
```

## Auth Flow

1. User logs in with NIK and password from `superapps_dev.public.users`.
2. The login request checks `public.user_access` for `aplikasi=config('tindakaudit.app_code')`.
3. Users listed in `tindakaudit.spi` bypass the access gate because SPI is treated as an equivalent identity source.
4. Inertia shares the authenticated user with related HRIS employee data from the `superapps` connection.

## Data Boundaries

TindakAudit migrations do not create `users` or `hris.*` tables. Domain tables store external references as plain indexed values, such as NIK, `kode_unit`, `kode_bagian`, and `kode_subbagian`. Foreign keys are kept only inside the TindakAudit-owned tables.

`temuan.created_by` and `temuan_history.changed_by` store the actor NIK as `varchar(15)`, so audit history remains stable even though auth data lives in the shared superapps database.

## Setup Flow

Fresh clones are bootstrapped with `php artisan app:install` after dependencies, asset build, `.env`, and the two empty PostgreSQL databases are ready.

The command performs these steps:

1. Verifies required PHP extensions.
2. Copies `.env.example` to `.env` when `.env` is missing.
3. Generates `APP_KEY` when it is still empty.
4. Checks both database connections: `pgsql` and `superapps`.
5. Creates the minimal `superapps_dev` tables from `database/sql/superapps_bootstrap.sql` when HRIS/auth tables are missing.
6. Runs TindakAudit migrations on the default `pgsql` connection.
7. Runs seeders unless `--no-seed` is passed.
8. Creates the `public/storage` link.

The bootstrap SQL contains only DDL for the tables this portfolio app needs from superapps: `public.users`, `public.user_access`, and `hris.unit_usaha`, `bagian`, `sub_bagian`, `karyawan`, `holiday`. It does not ship production data and does not touch other superapps schemas.
