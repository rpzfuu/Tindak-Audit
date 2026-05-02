<?php

namespace App\Http\Controllers;

use App\Models\HRIS\Bagian;
use App\Models\HRIS\Karyawan;
use App\Models\HRIS\UnitUsaha;
use App\Models\TindakAudit\Bidang;
use App\Models\TindakAudit\Notifikasi;
use App\Models\TindakAudit\Rekomendasi;
use App\Models\TindakAudit\RekomendasiHistory;
use App\Models\TindakAudit\Temuan;
use App\Models\TindakAudit\TemuanHistory;
use App\Models\User;
use App\Services\WhatsAppService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class ApiController extends Controller
{
    private const STATUS_DRAFT = 'Draft';

    private const STATUS_TERBUKA = 'Terbuka';

    private const STATUS_PROSES = 'Sedang Diproses';

    private const STATUS_MENUNGGU_VALIDASI = 'Menunggu Validasi';

    private const STATUS_DIVALIDASI = 'Divalidasi';

    private const STATUS_SELESAI = 'Selesai';

    public function index(): string
    {
        return 'Welcome to API';
    }

    public function getUnit(): JsonResponse
    {
        try {
            $data = UnitUsaha::with(['bagian.sub_bagian'])
                ->get();

            return $this->success('Berhasil mengambil data Unit', $data);
        } catch (Throwable $e) {
            return $this->error($e, 'Gagal mengambil data Unit');
        }
    }

    public function getBidang(): JsonResponse
    {
        try {
            return $this->success('Berhasil mengambil data Bidang', Bidang::get());
        } catch (Throwable $e) {
            return $this->error($e, 'Gagal mengambil data Bidang');
        }
    }

    public function inputTemuan(Request $request): JsonResponse
    {
        $this->authorizeSpi();

        $validated = $request->validate([
            'kode_unit' => ['required', 'string', 'max:50'],
            'temuan' => ['required', 'string'],
            'rekomendasi' => ['required', 'array', 'min:1'],
            'rekomendasi.*' => ['required', 'string'],
            'bidang_id' => ['required', 'integer'],
            'kode_bagian' => ['nullable', 'string', 'max:50'],
            'kode_subbagian' => ['nullable', 'string', 'max:50'],
        ]);

        try {
            DB::transaction(function () use ($validated) {
                $temuan = Temuan::create([
                    'created_by' => $this->currentNik(),
                    'kode_unit' => $validated['kode_unit'],
                    'temuan' => $validated['temuan'],
                    'bidang_id' => $validated['bidang_id'],
                    'kode_bagian' => $validated['kode_bagian'] ?? null,
                    'kode_subbagian' => $validated['kode_subbagian'] ?? null,
                    'status' => self::STATUS_DRAFT,
                ]);

                $temuanHistory = $this->createTemuanHistory(
                    $temuan,
                    $this->currentNik(),
                    'Temuan Baru Dibuat',
                    'create',
                );

                foreach ($validated['rekomendasi'] as $rekomendasi) {
                    $createdRekomendasi = Rekomendasi::create([
                        'temuan_id' => $temuan->id,
                        'rekomendasi' => $rekomendasi,
                        'status' => 'Menunggu Tindak Lanjut',
                    ]);

                    $this->createRekomendasiHistory($temuanHistory, $createdRekomendasi, 'create');
                }
            });

            return response()->json([
                'success' => true,
                'message' => 'Berhasil Input Temuan',
            ]);
        } catch (Throwable $e) {
            return $this->error($e, 'Gagal Input Temuan');
        }
    }

    public function deleteTemuan(Request $request): JsonResponse
    {
        $this->authorizeSpi();

        $validated = $request->validate([
            'id' => ['required', 'integer'],
        ]);

        try {
            DB::transaction(function () use ($validated) {
                $temuan = Temuan::with('rekomendasi', 'temuan_history.rekomendasi_history', 'notifikasi')
                    ->findOrFail($validated['id']);

                foreach ($temuan->temuan_history as $temuanHistory) {
                    $temuanHistory->rekomendasi_history()->delete();
                }

                $temuan->temuan_history()->delete();
                $temuan->rekomendasi()->delete();
                $temuan->notifikasi()->delete();
                $temuan->delete();
            });

            return response()->json([
                'success' => true,
                'message' => 'Temuan Berhasil Dihapus',
            ]);
        } catch (Throwable $e) {
            return $this->error($e, 'Gagal Menghapus Temuan');
        }
    }

    public function deleteRekomendasi(Request $request): JsonResponse
    {
        $this->authorizeSpi();

        $validated = $request->validate([
            'id' => ['required', 'integer'],
        ]);

        try {
            DB::transaction(function () use ($validated) {
                $rekomendasi = Rekomendasi::with('rekomendasi_history')->findOrFail($validated['id']);

                foreach ($rekomendasi->rekomendasi_history as $rekomendasiHistory) {
                    $rekomendasiHistory->delete();
                }

                $rekomendasi->delete();
            });

            return response()->json([
                'success' => true,
                'message' => 'Rekomendasi Berhasil Dihapus',
            ]);
        } catch (Throwable $e) {
            return $this->error($e, 'Gagal Menghapus Rekomendasi');
        }
    }

    public function updateTemuan(Request $request): JsonResponse
    {
        $this->authorizeSpi();

        $validated = $request->validate([
            'id' => ['required', 'integer'],
            'kode_unit' => ['required', 'string', 'max:50'],
            'temuan' => ['required', 'string'],
            'bidang_id' => ['required', 'integer'],
            'kode_bagian' => ['nullable', 'string', 'max:50'],
            'kode_subbagian' => ['nullable', 'string', 'max:50'],
            'rekomendasi' => ['required', 'array', 'min:1'],
            'rekomendasi.*.id' => ['nullable', 'integer'],
            'rekomendasi.*.rekomendasi' => ['required', 'string'],
            'rekomendasi.*.status' => ['nullable', 'string'],
        ]);

        try {
            DB::transaction(function () use ($validated) {
                $temuan = Temuan::findOrFail($validated['id']);
                $temuan->update([
                    'temuan' => $validated['temuan'],
                    'kode_unit' => $validated['kode_unit'],
                    'bidang_id' => $validated['bidang_id'],
                    'kode_bagian' => $validated['kode_bagian'] ?? null,
                    'kode_subbagian' => $validated['kode_subbagian'] ?? null,
                ]);

                $updatedRekomendasi = [];

                foreach ($validated['rekomendasi'] as $rekom) {
                    if (! empty($rekom['id'])) {
                        $rekomendasi = Rekomendasi::where('temuan_id', $temuan->id)
                            ->findOrFail($rekom['id']);

                        $rekomendasi->update([
                            'rekomendasi' => $rekom['rekomendasi'],
                        ]);
                    } else {
                        $rekomendasi = Rekomendasi::create([
                            'temuan_id' => $temuan->id,
                            'rekomendasi' => $rekom['rekomendasi'],
                            'status' => $rekom['status'] ?? 'Menunggu Tindak Lanjut',
                        ]);
                    }

                    $updatedRekomendasi[] = $rekomendasi;
                }

                $temuanHistory = $this->createTemuanHistory(
                    $temuan->fresh(),
                    $this->currentNik(),
                    'Temuan Diperbarui',
                    'update',
                );

                foreach ($updatedRekomendasi as $rekomendasi) {
                    $this->createRekomendasiHistory($temuanHistory, $rekomendasi, 'update');
                }
            });

            return response()->json([
                'success' => true,
                'message' => 'Temuan Berhasil Diperbarui',
            ]);
        } catch (Throwable $e) {
            return $this->error($e, 'Gagal Perbarui Temuan');
        }
    }

    public function getTemuan(): JsonResponse
    {
        try {
            $query = Temuan::with('rekomendasi', 'unit_usaha', 'bidang', 'bagian', 'sub_bagian');

            if (! $this->isSpi()) {
                $this->scopeTemuanForCurrentUser($query)
                    ->where('status', '!=', self::STATUS_DRAFT);
            }

            return $this->success('Berhasil mengambil data Temuan', $query->get());
        } catch (Throwable $e) {
            return $this->error($e, 'Gagal mengambil data Temuan');
        }
    }

    public function getTemuanValidasi(): JsonResponse
    {
        try {
            $query = Temuan::with('rekomendasi', 'unit_usaha', 'bidang', 'bagian', 'sub_bagian')
                ->whereIn('status', [self::STATUS_MENUNGGU_VALIDASI, self::STATUS_DIVALIDASI]);

            if (! $this->isSpi()) {
                $this->scopeTemuanForCurrentUser($query);
            }

            return $this->success('Berhasil mengambil data Temuan', $query->get());
        } catch (Throwable $e) {
            return $this->error($e, 'Gagal mengambil data Temuan');
        }
    }

    public function countUnit(): JsonResponse
    {
        try {
            $unitUsaha = UnitUsaha::count();
            $bagian = Bagian::count();

            return $this->success('Berhasil menghitung data Unit', $unitUsaha + $bagian - 1);
        } catch (Throwable $e) {
            return $this->error($e, 'Gagal menghitung data Unit');
        }
    }

    public function countTemuan(): JsonResponse
    {
        try {
            return $this->success('Berhasil menghitung data Temuan', Temuan::count());
        } catch (Throwable $e) {
            return $this->error($e, 'Gagal menghitung data Temuan');
        }
    }

    public function countRekomendasi(): JsonResponse
    {
        try {
            return $this->success('Berhasil menghitung data Rekomendasi', Rekomendasi::count());
        } catch (Throwable $e) {
            return $this->error($e, 'Gagal menghitung data Rekomendasi');
        }
    }

    public function countValidasi(): JsonResponse
    {
        try {
            $data = RekomendasiHistory::whereHas('temuan_history', function (Builder $query) {
                $query->where('status', self::STATUS_DIVALIDASI);
            })->count();

            return $this->success('Berhasil menghitung data Validasi', $data);
        } catch (Throwable $e) {
            return $this->error($e, 'Gagal menghitung data Validasi');
        }
    }

    public function getTemuanHistory(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'temuan_id' => ['required', 'integer'],
        ]);

        try {
            $temuan = Temuan::findOrFail($validated['temuan_id']);
            $this->ensureCanAccessTemuan($temuan);

            $temuanHistory = TemuanHistory::with('rekomendasi_history', 'bidang', 'unit_usaha', 'bagian', 'karyawan')
                ->where('temuan_id', $validated['temuan_id'])
                ->orderBy('created_at')
                ->get();

            return $this->success('Berhasil Mengambil Data Temuan History', $temuanHistory);
        } catch (Throwable $e) {
            return $this->error($e, 'Gagal Mengambil Data Temuan History');
        }
    }

    public function inputTindakLanjut(Request $request): JsonResponse
    {
        abort_if($this->isSpi(), 403, 'Tindak lanjut hanya dapat diinput oleh unit atau bagian.');

        $validated = $request->validate([
            'rekomendasi' => ['required', 'string'],
            'bukti' => ['required', 'array', 'min:1'],
            'bukti.*' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ]);

        $rekomendasiArray = json_decode($validated['rekomendasi'], true);

        abort_if(! is_array($rekomendasiArray) || count($rekomendasiArray) === 0, 422, 'Tidak ada rekomendasi yang dikirim.');

        validator($rekomendasiArray, [
            '*.id' => ['required', 'integer'],
            '*.temuan_id' => ['required', 'integer'],
            '*.tindak_lanjut' => ['required', 'string'],
        ])->validate();

        abort_if(count($request->file('bukti', [])) !== count($rekomendasiArray), 422, 'Jumlah bukti harus sama dengan jumlah rekomendasi.');

        $storedFiles = [];

        try {
            $temuan = null;

            DB::transaction(function () use ($request, $rekomendasiArray, &$storedFiles, &$temuan) {
                $temuanId = $rekomendasiArray[0]['temuan_id'];
                $temuan = Temuan::findOrFail($temuanId);
                $this->ensureCanAccessTemuan($temuan);

                $latestTemuanHistory = TemuanHistory::where('temuan_id', $temuanId)
                    ->latest()
                    ->first();

                abort_if(! $latestTemuanHistory, 404, 'History Temuan tidak ditemukan.');

                $newTemuanHistory = $this->createTemuanHistory(
                    $temuan,
                    $this->currentNik(),
                    'Input Tindak Lanjut',
                    'tindaklanjut',
                    self::STATUS_MENUNGGU_VALIDASI,
                );

                $temuan->update([
                    'status' => self::STATUS_MENUNGGU_VALIDASI,
                ]);

                foreach ($rekomendasiArray as $index => $rekom) {
                    abort_if((int) $rekom['temuan_id'] !== (int) $temuan->id, 422, 'Rekomendasi tidak berada pada temuan yang sama.');

                    $rekomendasi = Rekomendasi::where('temuan_id', $temuan->id)->findOrFail($rekom['id']);
                    $bukti = $request->file("bukti.$index")->store('uploads');
                    $storedFiles[] = $bukti;

                    $rekomendasi->update([
                        'tindak_lanjut' => $rekom['tindak_lanjut'],
                        'status' => self::STATUS_MENUNGGU_VALIDASI,
                        'bukti' => $bukti,
                    ]);

                    $this->createRekomendasiHistory($newTemuanHistory, $rekomendasi->fresh(), 'tindaklanjut');
                }

                $this->createNotifikasi('4R00', '4SPI', 'tindaklanjut', $temuan->id, 'Temuan Ditindaklanjut');
            });

            if ($temuan instanceof Temuan) {
                $this->sendTindakLanjutNotification($temuan->fresh('rekomendasi', 'bidang', 'unit_usaha', 'bagian'));
            }

            return response()->json([
                'success' => true,
                'message' => 'Berhasil Input Tindak Lanjut',
            ]);
        } catch (Throwable $e) {
            foreach ($storedFiles as $storedFile) {
                Storage::disk('local')->delete($storedFile);
            }

            return $this->error($e, 'Gagal Input Tindak Lanjut');
        }
    }

    public function kirimTemuan(Request $request): JsonResponse
    {
        $this->authorizeSpi();

        $validated = $request->validate([
            'temuan_id' => ['required', 'integer'],
        ]);

        try {
            $temuan = DB::transaction(function () use ($validated) {
                $temuan = Temuan::findOrFail($validated['temuan_id']);
                $temuan->update([
                    'status' => self::STATUS_TERBUKA,
                ]);

                $this->createTemuanHistory($temuan->fresh(), $this->currentNik(), 'Temuan Dikirim', 'send');
                $this->createNotifikasi($temuan->kode_unit, $temuan->kode_bagian, 'send', $temuan->id, 'Ada Temuan Baru Di Unit Anda');

                return $temuan->fresh('rekomendasi', 'bidang', 'unit_usaha', 'bagian');
            });

            $this->sendTemuanBaruNotification($temuan);

            return response()->json([
                'success' => true,
                'message' => 'Berhasil Mengirim Temuan',
            ]);
        } catch (Throwable $e) {
            return $this->error($e, 'Gagal Mengirim Temuan');
        }
    }

    public function prosesTemuan(Request $request): JsonResponse
    {
        abort_if($this->isSpi(), 403, 'Temuan hanya dapat diproses oleh unit atau bagian.');

        $validated = $request->validate([
            'temuan_id' => ['required', 'integer'],
        ]);

        try {
            DB::transaction(function () use ($validated) {
                $temuan = Temuan::findOrFail($validated['temuan_id']);
                $this->ensureCanAccessTemuan($temuan);

                $temuan->update([
                    'status' => self::STATUS_PROSES,
                ]);

                $this->createTemuanHistory($temuan->fresh(), $this->currentNik(), 'Temuan Diproses', 'process');
                $this->createNotifikasi('4R00', '4SPI', 'process', $temuan->id, 'Temuan Diproses');
            });

            return response()->json([
                'success' => true,
                'message' => 'Berhasil Proses Temuan',
            ]);
        } catch (Throwable $e) {
            return $this->error($e, 'Gagal Proses Temuan');
        }
    }

    public function validasiTemuan(Request $request): JsonResponse
    {
        $this->authorizeSpi();

        $validated = $request->validate([
            'rekomendasi' => ['required', 'array', 'min:1'],
            'rekomendasi.*.id' => ['required', 'integer'],
            'rekomendasi.*.temuan_id' => ['required', 'integer'],
            'rekomendasi.*.tindak_lanjut' => ['nullable', 'string'],
            'rekomendasi.*.status' => [
                'required',
                Rule::in(['Sesuai', 'Tidak Sesuai', 'Belum Ditindaklanjut', 'Tidak Dapat Ditindaklanjut']),
            ],
            'rekomendasi.*.alasan' => ['required', 'string'],
        ]);

        try {
            $temuan = DB::transaction(function () use ($validated) {
                $rekomendasiArray = $validated['rekomendasi'];
                $temuanId = $rekomendasiArray[0]['temuan_id'];
                $temuan = Temuan::findOrFail($temuanId);

                $temuan->update([
                    'status' => self::STATUS_DIVALIDASI,
                ]);

                $temuanHistory = $this->createTemuanHistory(
                    $temuan->fresh(),
                    $this->currentNik(),
                    'Temuan Divalidasi',
                    'validation',
                );

                $this->createNotifikasi($temuan->kode_unit, $temuan->kode_bagian, 'validation', $temuan->id, 'Temuan Divalidasi');

                foreach ($rekomendasiArray as $rekom) {
                    abort_if((int) $rekom['temuan_id'] !== (int) $temuan->id, 422, 'Rekomendasi tidak berada pada temuan yang sama.');

                    $rekomendasi = Rekomendasi::where('temuan_id', $temuan->id)->findOrFail($rekom['id']);
                    $rekomendasi->update([
                        'status' => $rekom['status'],
                        'alasan' => $rekom['alasan'],
                    ]);

                    $this->createRekomendasiHistory($temuanHistory, $rekomendasi->fresh(), 'validation');
                }

                return $temuan->fresh('rekomendasi', 'bidang', 'unit_usaha', 'bagian');
            });

            $this->sendValidasiNotification($temuan);

            return response()->json([
                'success' => true,
                'message' => 'Berhasil Validasi Temuan',
            ]);
        } catch (Throwable $e) {
            return $this->error($e, 'Gagal Validasi Temuan');
        }
    }

    public function unitCekValidasi(Request $request): JsonResponse
    {
        abort_if($this->isSpi(), 403, 'Konfirmasi validasi hanya dapat dilakukan oleh unit atau bagian.');

        $validated = $request->validate([
            'temuan_id' => ['required', 'integer'],
            'rekomendasi' => ['required', 'array', 'min:1'],
            'rekomendasi.*.id' => ['required', 'integer'],
        ]);

        try {
            DB::transaction(function () use ($validated) {
                $temuan = Temuan::findOrFail($validated['temuan_id']);
                $this->ensureCanAccessTemuan($temuan);

                $allRekomendasiSesuai = collect($validated['rekomendasi'])->every(function (array $rekom) use ($temuan) {
                    return Rekomendasi::where('temuan_id', $temuan->id)
                        ->where('id', $rekom['id'])
                        ->where('status', 'Sesuai')
                        ->exists();
                });

                $temuan->update([
                    'status' => $allRekomendasiSesuai ? self::STATUS_SELESAI : self::STATUS_TERBUKA,
                ]);

                $this->createTemuanHistory(
                    $temuan->fresh(),
                    $this->currentNik(),
                    $allRekomendasiSesuai ? 'Audit Selesai' : 'Tindak Lanjut Belum Sesuai',
                    'checked',
                );
            });

            return response()->json([
                'success' => true,
                'message' => 'Berhasil Konfirmasi Hasil Validasi',
            ]);
        } catch (Throwable $e) {
            return $this->error($e, 'Gagal Konfirmasi Hasil Validasi');
        }
    }

    public function getNotifikasi(): JsonResponse
    {
        try {
            $notifikasi = $this->notificationQueryForCurrentUser()
                ->with('temuan')
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Berhasil Mengambil Notifikasi',
                'data' => $notifikasi,
                'notification_count' => $notifikasi->where('read', false)->count(),
            ]);
        } catch (Throwable $e) {
            return $this->error($e, 'Gagal Mengambil Notifikasi');
        }
    }

    public function readNotifikasi(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'notifikasi_id' => ['required', 'integer'],
        ]);

        try {
            $notifikasi = Notifikasi::findOrFail($validated['notifikasi_id']);
            $this->ensureCanAccessNotifikasi($notifikasi);

            $notifikasi->update(['read' => true]);

            return response()->json([
                'success' => true,
                'message' => 'Berhasil Membaca Notifikasi',
            ]);
        } catch (Throwable $e) {
            return $this->error($e, 'Gagal Membaca Notifikasi');
        }
    }

    public function readAllNotifikasi(): JsonResponse
    {
        try {
            $this->notificationQueryForCurrentUser()
                ->where('read', false)
                ->update([
                    'read' => true,
                    'updated_at' => now(),
                ]);

            return response()->json([
                'success' => true,
                'message' => 'Berhasil Membaca Notifikasi',
            ]);
        } catch (Throwable $e) {
            return $this->error($e, 'Gagal Membaca Notifikasi');
        }
    }

    public function downloadBukti(Rekomendasi $rekomendasi)
    {
        $this->ensureCanAccessTemuan($rekomendasi->temuan()->firstOrFail());

        abort_if(empty($rekomendasi->bukti), 404, 'Bukti tidak ditemukan.');

        $disk = 'local';
        $path = $rekomendasi->bukti;

        if (str_starts_with($path, 'public/')) {
            $disk = 'public';
            $path = substr($path, strlen('public/'));
        }

        abort_unless(Storage::disk($disk)->exists($path), 404, 'Bukti tidak ditemukan.');

        return Storage::disk($disk)->download($path);
    }

    private function getBagianCode(?string $name): ?string
    {
        if (empty($name)) {
            return null;
        }

        $bagian = Bagian::where('code', $name)
            ->orWhereRaw('LOWER(name) = ?', [strtolower($name)])
            ->first();

        if ($bagian) {
            return $bagian->code;
        }

        $listBagian = [
            'BAGIAN TANAMAN' => '4TAN',
            'BAGIAN TEKNIK & PENGOLAHAN' => '4TEP',
            'BAGIAN SEKRETARIAT & HUKUM' => '4SKH',
            'BAGIAN SDM & SISTEM MANAJEMEN' => '4SDM',
            'BAGIAN KEUANGAN & AKUNTANSI' => '4AKN',
            'BAGIAN PENGADAAN & TEKNOLOGI INFORMASI' => '4PTI',
            'SATUAN PENGAWAS INTERN' => '4SPI',
        ];

        return $listBagian[strtoupper($name)] ?? null;
    }

    private function currentUser(): User
    {
        $user = Auth::user();

        abort_if(! $user instanceof User, 401, 'Unauthenticated.');

        return $user;
    }

    private function currentNik(): string
    {
        return (string) $this->currentUser()->nik;
    }

    private function currentKaryawan(): Karyawan
    {
        $karyawan = Karyawan::where('nik', $this->currentNik())->first();

        abort_if(! $karyawan, 403, 'Profil karyawan tidak ditemukan.');

        return $karyawan;
    }

    private function isSpi(): bool
    {
        return $this->currentUser()->isSpi();
    }

    private function authorizeSpi(): void
    {
        abort_unless($this->isSpi(), 403, 'Aksi ini hanya dapat dilakukan oleh SPI.');
    }

    private function scopeTemuanForCurrentUser(Builder $query): Builder
    {
        $karyawan = $this->currentKaryawan();

        if ($karyawan->kode_unit === '4R00') {
            $kodeBagian = $this->getBagianCode($karyawan->sub_unit);
            abort_if(empty($kodeBagian), 403, 'Kode bagian pengguna tidak dikenali.');

            return $query->where('kode_unit', '4R00')
                ->where('kode_bagian', $kodeBagian);
        }

        return $query->where('kode_unit', $karyawan->kode_unit);
    }

    private function ensureCanAccessTemuan(Temuan $temuan): void
    {
        if ($this->isSpi()) {
            return;
        }

        $karyawan = $this->currentKaryawan();

        if ($karyawan->kode_unit === '4R00') {
            $kodeBagian = $this->getBagianCode($karyawan->sub_unit);
            abort_unless($temuan->kode_unit === '4R00' && $temuan->kode_bagian === $kodeBagian, 403, 'Anda tidak dapat mengakses temuan ini.');

            return;
        }

        abort_unless($temuan->kode_unit === $karyawan->kode_unit, 403, 'Anda tidak dapat mengakses temuan ini.');
    }

    private function ensureCanAccessNotifikasi(Notifikasi $notifikasi): void
    {
        if ($this->isSpi()) {
            abort_unless($notifikasi->kode_bagian === '4SPI', 403, 'Anda tidak dapat mengakses notifikasi ini.');

            return;
        }

        $karyawan = $this->currentKaryawan();

        if ($karyawan->kode_unit === '4R00') {
            abort_unless($notifikasi->kode_bagian === $this->getBagianCode($karyawan->sub_unit), 403, 'Anda tidak dapat mengakses notifikasi ini.');

            return;
        }

        abort_unless($notifikasi->kode_unit === $karyawan->kode_unit, 403, 'Anda tidak dapat mengakses notifikasi ini.');
    }

    private function notificationQueryForCurrentUser(): Builder
    {
        $query = Notifikasi::query();

        if ($this->isSpi()) {
            return $query->where('kode_bagian', '4SPI');
        }

        $karyawan = $this->currentKaryawan();

        if ($karyawan->kode_unit === '4R00') {
            return $query->where('kode_bagian', $this->getBagianCode($karyawan->sub_unit));
        }

        return $query->where('kode_unit', $karyawan->kode_unit);
    }

    private function createTemuanHistory(Temuan $temuan, string $changedBy, string $keterangan, string $action, ?string $status = null): TemuanHistory
    {
        return TemuanHistory::create([
            'temuan_id' => $temuan->id,
            'temuan' => $temuan->temuan,
            'kode_unit' => $temuan->kode_unit,
            'bidang_id' => $temuan->bidang_id,
            'kode_bagian' => $temuan->kode_bagian,
            'status' => $status ?? $temuan->status,
            'changed_by' => $changedBy,
            'keterangan' => $keterangan,
            'action' => $action,
        ]);
    }

    private function createRekomendasiHistory(TemuanHistory $temuanHistory, Rekomendasi $rekomendasi, string $action): RekomendasiHistory
    {
        return RekomendasiHistory::create([
            'temuan_history_id' => $temuanHistory->id,
            'rekomendasi_id' => $rekomendasi->id,
            'rekomendasi' => $rekomendasi->rekomendasi,
            'status' => $rekomendasi->status,
            'alasan' => $rekomendasi->alasan,
            'tindak_lanjut' => $rekomendasi->tindak_lanjut,
            'action' => $action,
        ]);
    }

    private function createNotifikasi(?string $kodeUnit, ?string $kodeBagian, string $action, int $temuanId, string $message): Notifikasi
    {
        return Notifikasi::create([
            'temuan_id' => $temuanId,
            'kode_unit' => $kodeUnit ?? '4R00',
            'kode_bagian' => $kodeBagian,
            'action' => $action,
            'message' => $message,
            'read' => false,
        ]);
    }

    private function sendTemuanBaruNotification(Temuan $temuan): void
    {
        $recipient = $this->temuanRecipientName($temuan);
        $bidang = $temuan->bidang?->nama ?? '-';
        $message = "[Sistem Tindak Lanjut Audit]\n\n";
        $message .= "Halo, {$recipient}!\n\n";
        $message .= "Telah ditemukan temuan audit baru dari SPI:\n\n";
        $message .= "Judul Temuan: {$temuan->temuan}\n";
        $message .= "Bidang: {$bidang}\n\n";
        $message .= "Rekomendasi:\n";

        foreach ($temuan->rekomendasi as $index => $rekomendasi) {
            $message .= ($index + 1).". {$rekomendasi->rekomendasi}\n";
        }

        $message .= "\nMohon segera berikan tindak lanjut melalui TindakAudit. Terima kasih.";

        WhatsAppService::sendMessage(config('services.wablas.debug_phone'), $message);
    }

    private function sendTindakLanjutNotification(Temuan $temuan): void
    {
        $recipient = $this->temuanRecipientName($temuan);
        $bidang = $temuan->bidang?->nama ?? '-';
        $message = "[Sistem Tindak Lanjut Audit]\n\n";
        $message .= "Halo, SPI!\n\n";
        $message .= "{$recipient} telah mengunggah bukti untuk temuan berikut:\n\n";
        $message .= "Judul Temuan: {$temuan->temuan}\n";
        $message .= "Bidang: {$bidang}\n\n";
        $message .= "Tindak Lanjut:\n";

        foreach ($temuan->rekomendasi as $index => $rekomendasi) {
            $message .= ($index + 1).". {$rekomendasi->tindak_lanjut}\n";
        }

        $message .= "\nSilakan cek bukti tersebut dan lakukan validasi jika sudah sesuai.";

        WhatsAppService::sendMessage(config('services.wablas.debug_phone'), $message);
    }

    private function sendValidasiNotification(Temuan $temuan): void
    {
        $recipient = $this->temuanRecipientName($temuan);
        $bidang = $temuan->bidang?->nama ?? '-';
        $message = "[Sistem Tindak Lanjut Audit]\n\n";
        $message .= "Halo, {$recipient}!\n\n";
        $message .= "Tindak lanjut Anda untuk temuan berikut telah divalidasi oleh SPI:\n\n";
        $message .= "Judul Temuan: {$temuan->temuan}\n";
        $message .= "Bidang: {$bidang}\n\n";
        $message .= 'Mohon segera dicek hasil validasi melalui TindakAudit. Terima kasih.';

        WhatsAppService::sendMessage(config('services.wablas.debug_phone'), $message);
    }

    private function temuanRecipientName(Temuan $temuan): string
    {
        if ($temuan->kode_unit === '4R00') {
            return $temuan->bagian?->name ?? 'Bagian';
        }

        return $temuan->unit_usaha?->nama_unit ?? 'Unit';
    }

    private function success(string $message, mixed $data): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ]);
    }

    private function error(Throwable $e, string $message): JsonResponse
    {
        $status = $e instanceof HttpExceptionInterface ? $e->getStatusCode() : 500;

        return response()->json([
            'success' => false,
            'message' => $message.': '.$e->getMessage(),
            'data' => null,
        ], $status);
    }
}
