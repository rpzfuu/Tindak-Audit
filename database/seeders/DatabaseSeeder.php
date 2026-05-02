<?php

namespace Database\Seeders;

use App\Models\TindakAudit\Bidang;
use App\Models\TindakAudit\Notifikasi;
use App\Models\TindakAudit\Rekomendasi;
use App\Models\TindakAudit\RekomendasiHistory;
use App\Models\TindakAudit\Temuan;
use App\Models\TindakAudit\TemuanHistory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $this->seedSuperapps($now);

        DB::transaction(function () use ($now) {
            foreach (['19990001', '19990002'] as $nikSpi) {
                DB::table('tindakaudit.spi')->updateOrInsert(
                    ['nik' => $nikSpi],
                    ['nik' => $nikSpi],
                );
            }

            foreach (['Operasional', 'Keuangan', 'SDM', 'Pengadaan', 'Tanaman'] as $namaBidang) {
                Bidang::updateOrCreate(['nama' => $namaBidang], ['updated_at' => $now]);
            }

            Storage::disk('local')->put(
                'uploads/demo-bukti.pdf',
                "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\ntrailer\n<< /Root 1 0 R >>\n%%EOF\n",
            );

            $operasional = Bidang::where('nama', 'Operasional')->firstOrFail();
            $keuangan = Bidang::where('nama', 'Keuangan')->firstOrFail();
            $sdm = Bidang::where('nama', 'SDM')->firstOrFail();
            $tanaman = Bidang::where('nama', 'Tanaman')->firstOrFail();

            $draftTemuan = Temuan::updateOrCreate(
                ['temuan' => 'Dokumen monitoring persediaan belum diperbarui secara berkala'],
                [
                    'created_by' => '19990001',
                    'kode_unit' => '4U01',
                    'bidang_id' => $operasional->id,
                    'kode_bagian' => null,
                    'kode_subbagian' => null,
                    'status' => 'Draft',
                ],
            );
            $this->syncTemuanHistory($draftTemuan, '19990001', 'Temuan Baru Dibuat', 'create');
            $this->syncRekomendasi($draftTemuan, [
                'Memperbarui dokumen monitoring persediaan setiap akhir bulan.',
            ]);

            $openTemuan = Temuan::updateOrCreate(
                ['temuan' => 'Rekonsiliasi biaya pemeliharaan belum memiliki lampiran lengkap'],
                [
                    'created_by' => '19990001',
                    'kode_unit' => '4U01',
                    'bidang_id' => $keuangan->id,
                    'kode_bagian' => null,
                    'kode_subbagian' => null,
                    'status' => 'Terbuka',
                ],
            );
            $this->syncTemuanHistory($openTemuan, '19990001', 'Temuan Dikirim', 'send');
            $this->syncRekomendasi($openTemuan, [
                'Melengkapi lampiran rekonsiliasi biaya pemeliharaan pada periode berjalan.',
                'Menetapkan PIC verifikasi dokumen sebelum tutup buku.',
            ]);

            $processedTemuan = Temuan::updateOrCreate(
                ['temuan' => 'Kalibrasi alat ukur produksi belum terdokumentasi lengkap'],
                [
                    'created_by' => '19990002',
                    'kode_unit' => '4U02',
                    'bidang_id' => $operasional->id,
                    'kode_bagian' => null,
                    'kode_subbagian' => null,
                    'status' => 'Sedang Diproses',
                ],
            );
            $this->syncTemuanHistory($processedTemuan, '19990002', 'Temuan Dikirim', 'send');
            $this->syncTemuanHistory($processedTemuan, '19990004', 'Temuan Diproses', 'process');
            $this->syncRekomendasi($processedTemuan, [
                'Melakukan kalibrasi ulang dan mengunggah berita acara pemeriksaan alat.',
            ]);

            $waitingValidationTemuan = Temuan::updateOrCreate(
                ['temuan' => 'SOP pengarsipan bukti pembayaran belum dipatuhi seluruh bagian'],
                [
                    'created_by' => '19990001',
                    'kode_unit' => '4R00',
                    'bidang_id' => $sdm->id,
                    'kode_bagian' => '4SDM',
                    'kode_subbagian' => '4SDM-PRS',
                    'status' => 'Menunggu Validasi',
                ],
            );
            $waitingHistory = $this->syncTemuanHistory($waitingValidationTemuan, '19990007', 'Input Tindak Lanjut', 'tindaklanjut');
            $this->syncRekomendasi($waitingValidationTemuan, [
                'Melakukan sosialisasi ulang SOP pengarsipan bukti pembayaran.',
            ], [
                'Sosialisasi ulang telah dilakukan dan daftar hadir sudah diunggah.',
            ], 'uploads/demo-bukti.pdf');

            foreach ($waitingValidationTemuan->rekomendasi as $rekomendasi) {
                RekomendasiHistory::updateOrCreate(
                    [
                        'temuan_history_id' => $waitingHistory->id,
                        'rekomendasi_id' => $rekomendasi->id,
                    ],
                    [
                        'rekomendasi' => $rekomendasi->rekomendasi,
                        'status' => $rekomendasi->status,
                        'tindak_lanjut' => $rekomendasi->tindak_lanjut,
                        'action' => 'tindaklanjut',
                    ],
                );
            }

            $finishedTemuan = Temuan::updateOrCreate(
                ['temuan' => 'Kartu inspeksi kebun belum ditandatangani supervisor'],
                [
                    'created_by' => '19990002',
                    'kode_unit' => '4R00',
                    'bidang_id' => $tanaman->id,
                    'kode_bagian' => '4TAN',
                    'kode_subbagian' => '4TAN-BDY',
                    'status' => 'Selesai',
                ],
            );
            $this->syncTemuanHistory($finishedTemuan, '19990002', 'Temuan Dikirim', 'send');
            $this->syncTemuanHistory($finishedTemuan, '19990005', 'Input Tindak Lanjut', 'tindaklanjut', 'Menunggu Validasi');
            $this->syncTemuanHistory($finishedTemuan, '19990001', 'Temuan Divalidasi', 'validation', 'Divalidasi');
            $this->syncTemuanHistory($finishedTemuan, '19990005', 'Audit Selesai', 'checked');
            $this->syncRekomendasi($finishedTemuan, [
                'Melengkapi tanda tangan supervisor pada kartu inspeksi berjalan.',
            ], [
                'Supervisor sudah menandatangani kartu inspeksi dan bukti sudah diunggah.',
            ], 'uploads/demo-bukti.pdf', 'Sesuai', 'Bukti sesuai dengan rekomendasi.');

            foreach ([
                [
                    'temuan_id' => $waitingValidationTemuan->id,
                    'kode_unit' => '4R00',
                    'kode_bagian' => '4SPI',
                    'action' => 'tindaklanjut',
                    'message' => 'Temuan Ditindaklanjut',
                ],
                [
                    'temuan_id' => $openTemuan->id,
                    'kode_unit' => '4U01',
                    'kode_bagian' => null,
                    'action' => 'send',
                    'message' => 'Ada Temuan Baru Di Unit Anda',
                ],
                [
                    'temuan_id' => $finishedTemuan->id,
                    'kode_unit' => '4R00',
                    'kode_bagian' => '4TAN',
                    'action' => 'validation',
                    'message' => 'Temuan Divalidasi',
                ],
            ] as $notifikasi) {
                Notifikasi::updateOrCreate(
                    [
                        'temuan_id' => $notifikasi['temuan_id'],
                        'kode_unit' => $notifikasi['kode_unit'],
                        'kode_bagian' => $notifikasi['kode_bagian'],
                        'action' => $notifikasi['action'],
                    ],
                    [
                        'message' => $notifikasi['message'],
                        'read' => false,
                    ],
                );
            }
        });
    }

    private function seedSuperapps($now): void
    {
        $sa = DB::connection('superapps');

        $sa->transaction(function () use ($sa, $now) {
            $this->syncSuperappsSequences($sa);

            foreach ([
                [
                    'kode_unit' => '4R00',
                    'nama_unit' => 'Kantor Direksi',
                    'kode_grup_unit' => 'HO',
                    'nama_grup_unit' => 'Head Office',
                    'is_head_office' => true,
                ],
                [
                    'kode_unit' => '4U01',
                    'nama_unit' => 'Unit Produksi Utara',
                    'kode_grup_unit' => 'UPR',
                    'nama_grup_unit' => 'Unit Produksi',
                    'is_head_office' => false,
                ],
                [
                    'kode_unit' => '4U02',
                    'nama_unit' => 'Unit Produksi Selatan',
                    'kode_grup_unit' => 'UPR',
                    'nama_grup_unit' => 'Unit Produksi',
                    'is_head_office' => false,
                ],
            ] as $unit) {
                $sa->table('hris.unit_usaha')->updateOrInsert(
                    ['kode_unit' => $unit['kode_unit']],
                    [
                        ...$unit,
                        'is_saturday_on' => false,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ],
                );
            }

            foreach ([
                ['kode_unit' => '4R00', 'name' => 'SATUAN PENGAWAS INTERN', 'code' => '4SPI'],
                ['kode_unit' => '4R00', 'name' => 'BAGIAN TANAMAN', 'code' => '4TAN'],
                ['kode_unit' => '4R00', 'name' => 'BAGIAN TEKNIK & PENGOLAHAN', 'code' => '4TEP'],
                ['kode_unit' => '4R00', 'name' => 'BAGIAN SEKRETARIAT & HUKUM', 'code' => '4SKH'],
                ['kode_unit' => '4R00', 'name' => 'BAGIAN SDM & SISTEM MANAJEMEN', 'code' => '4SDM'],
                ['kode_unit' => '4R00', 'name' => 'BAGIAN KEUANGAN & AKUNTANSI', 'code' => '4AKN'],
                ['kode_unit' => '4R00', 'name' => 'BAGIAN PENGADAAN & TEKNOLOGI INFORMASI', 'code' => '4PTI'],
                ['kode_unit' => '4U01', 'name' => 'AFDELING I', 'code' => '4U01-AFD1'],
                ['kode_unit' => '4U02', 'name' => 'PENGOLAHAN', 'code' => '4U02-PGL'],
            ] as $bagian) {
                $sa->table('hris.bagian')->updateOrInsert(
                    ['code' => $bagian['code']],
                    [
                        ...$bagian,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ],
                );
            }

            foreach ([
                ['bagian_code' => '4TAN', 'name' => 'Sub Bagian Budidaya', 'code' => '4TAN-BDY'],
                ['bagian_code' => '4TEP', 'name' => 'Sub Bagian Teknik', 'code' => '4TEP-TEK'],
                ['bagian_code' => '4SDM', 'name' => 'Sub Bagian Personalia', 'code' => '4SDM-PRS'],
                ['bagian_code' => '4U01-AFD1', 'name' => 'Blok A', 'code' => '4U01-AFD1-A'],
                ['bagian_code' => '4U02-PGL', 'name' => 'Stasiun Pengolahan', 'code' => '4U02-PGL-A'],
            ] as $subBagian) {
                $sa->table('hris.sub_bagian')->updateOrInsert(
                    ['code' => $subBagian['code']],
                    [
                        ...$subBagian,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ],
                );
            }

            $karyawans = [
                [
                    'nik' => '19990001',
                    'nama' => 'Demo SPI Utama',
                    'kode_unit' => '4R00',
                    'sub_unit' => 'SATUAN PENGAWAS INTERN',
                    'jabatan' => 'Auditor Internal',
                    'jenkel' => 'Laki-laki',
                    'no_hp' => '6281200000001',
                ],
                [
                    'nik' => '19990002',
                    'nama' => 'Demo SPI Reviewer',
                    'kode_unit' => '4R00',
                    'sub_unit' => 'SATUAN PENGAWAS INTERN',
                    'jabatan' => 'Kepala SPI',
                    'jenkel' => 'Perempuan',
                    'no_hp' => '6281200000002',
                ],
                [
                    'nik' => '19990003',
                    'nama' => 'Demo Unit Utara',
                    'kode_unit' => '4U01',
                    'sub_unit' => 'AFDELING I',
                    'jabatan' => 'Asisten Afdeling',
                    'jenkel' => 'Laki-laki',
                    'no_hp' => '6281200000003',
                ],
                [
                    'nik' => '19990004',
                    'nama' => 'Demo Unit Selatan',
                    'kode_unit' => '4U02',
                    'sub_unit' => 'PENGOLAHAN',
                    'jabatan' => 'Asisten Pengolahan',
                    'jenkel' => 'Perempuan',
                    'no_hp' => '6281200000004',
                ],
                [
                    'nik' => '19990005',
                    'nama' => 'Demo Bagian Tanaman',
                    'kode_unit' => '4R00',
                    'sub_unit' => 'BAGIAN TANAMAN',
                    'jabatan' => 'Staf Tanaman',
                    'jenkel' => 'Laki-laki',
                    'no_hp' => '6281200000005',
                ],
                [
                    'nik' => '19990006',
                    'nama' => 'Demo Bagian Keuangan',
                    'kode_unit' => '4R00',
                    'sub_unit' => 'BAGIAN KEUANGAN & AKUNTANSI',
                    'jabatan' => 'Staf Akuntansi',
                    'jenkel' => 'Perempuan',
                    'no_hp' => '6281200000006',
                ],
                [
                    'nik' => '19990007',
                    'nama' => 'Demo Bagian SDM',
                    'kode_unit' => '4R00',
                    'sub_unit' => 'BAGIAN SDM & SISTEM MANAJEMEN',
                    'jabatan' => 'Staf SDM',
                    'jenkel' => 'Laki-laki',
                    'no_hp' => '6281200000007',
                ],
                [
                    'nik' => '19990008',
                    'nama' => 'Demo Bagian Pengadaan',
                    'kode_unit' => '4R00',
                    'sub_unit' => 'BAGIAN PENGADAAN & TEKNOLOGI INFORMASI',
                    'jabatan' => 'Staf Pengadaan',
                    'jenkel' => 'Perempuan',
                    'no_hp' => '6281200000008',
                ],
            ];

            foreach ($karyawans as $karyawan) {
                $sa->table('hris.karyawan')->updateOrInsert(
                    ['nik' => $karyawan['nik']],
                    [
                        ...$karyawan,
                        'suskel' => 'K0',
                        'ptkp' => 'TK/0',
                        'egrup' => 'Staff',
                        'esubgrup' => 'Demo',
                        'pendidikan' => 'S1',
                        'tanggal_masuk' => '2024-01-01',
                        'tanggal_cuti_tahunan' => '2024-01-01',
                        'tanggal_cuti_panjang' => null,
                        'tanggal_lahir' => '1998-01-01',
                        'bod' => 'N',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ],
                );

                $sa->table('public.users')->updateOrInsert(
                    ['nik' => $karyawan['nik']],
                    [
                        'password' => Hash::make('password'),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ],
                );

                $this->grantAppAccess($sa, $karyawan['nik'], $now);
            }

            foreach (config('tindakaudit.real_access_niks', []) as $nik) {
                $hasKaryawan = $sa->table('hris.karyawan')->where('nik', $nik)->exists();
                $hasUser = $sa->table('public.users')->where('nik', $nik)->exists();

                if ($hasKaryawan && $hasUser) {
                    $this->grantAppAccess($sa, $nik, $now);
                }
            }

            $sa->table('hris.holiday')->updateOrInsert(
                ['date' => '2026-01-01'],
                [
                    'name' => 'Tahun Baru',
                    'type' => 'national',
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            );
        });
    }

    private function syncSuperappsSequences($connection): void
    {
        foreach ([
            ['hris.unit_usaha', 'id', 'hris.unit_usaha_id_seq'],
            ['hris.bagian', 'id', 'hris.bagian_id_seq'],
            ['hris.sub_bagian', 'id', 'hris.sub_bagian_id_seq'],
            ['hris.karyawan', 'id', 'hris.karyawan_id_seq'],
            ['hris.holiday', 'id', 'hris.holiday_id_seq'],
            ['public.users', 'id', 'public.users_id_seq'],
            ['public.user_access', 'id', 'public.user_access_id_seq'],
        ] as [$table, $column, $sequence]) {
            $connection->statement(
                "SELECT setval('{$sequence}', COALESCE((SELECT MAX({$column}) FROM {$table}), 0) + 1, false)",
            );
        }
    }

    private function grantAppAccess($connection, string $nik, $now): void
    {
        $connection->table('public.user_access')->updateOrInsert(
            [
                'nik' => $nik,
                'aplikasi' => config('tindakaudit.app_code'),
            ],
            [
                'created_at' => $now,
                'updated_at' => $now,
            ],
        );
    }

    private function syncTemuanHistory(Temuan $temuan, string $nik, string $keterangan, string $action, ?string $status = null): TemuanHistory
    {
        return TemuanHistory::updateOrCreate(
            [
                'temuan_id' => $temuan->id,
                'action' => $action,
            ],
            [
                'temuan' => $temuan->temuan,
                'kode_unit' => $temuan->kode_unit,
                'bidang_id' => $temuan->bidang_id,
                'kode_bagian' => $temuan->kode_bagian,
                'status' => $status ?? $temuan->status,
                'changed_by' => $nik,
                'keterangan' => $keterangan,
            ],
        );
    }

    /**
     * @param  array<int, string>  $items
     * @param  array<int, string>  $tindakLanjut
     */
    private function syncRekomendasi(
        Temuan $temuan,
        array $items,
        array $tindakLanjut = [],
        ?string $bukti = null,
        ?string $status = null,
        ?string $alasan = null,
    ): void {
        foreach ($items as $index => $item) {
            Rekomendasi::updateOrCreate(
                [
                    'temuan_id' => $temuan->id,
                    'rekomendasi' => $item,
                ],
                [
                    'status' => $status ?? (isset($tindakLanjut[$index]) ? 'Menunggu Validasi' : 'Menunggu Tindak Lanjut'),
                    'alasan' => $alasan,
                    'tindak_lanjut' => $tindakLanjut[$index] ?? null,
                    'bukti' => isset($tindakLanjut[$index]) ? $bukti : null,
                ],
            );
        }
    }
}
