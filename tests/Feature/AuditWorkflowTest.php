<?php

use App\Models\TindakAudit\Bidang;
use App\Models\TindakAudit\Rekomendasi;
use App\Models\TindakAudit\Temuan;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    config(['services.wablas.driver' => 'disabled']);

    Storage::fake('local');

    $this->seed();
});

test('spi and unit users can complete the audit workflow', function () {
    $loginAsNik = function (string $nik): User {
        $user = User::where('nik', $nik)->firstOrFail();

        $this->post('/login', [
            'nik' => $nik,
            'password' => 'password',
        ])->assertRedirect(route('dashboard.index', absolute: false));

        $this->assertAuthenticatedAs($user);

        return $user;
    };

    $logout = function (): void {
        $this->post('/logout')->assertRedirect('/');
        $this->assertGuest();
    };

    $spi = $loginAsNik('19990001');
    $bidang = Bidang::where('nama', 'Operasional')->firstOrFail();
    $judulTemuan = 'Smoke test temuan audit '.now()->timestamp;

    $this->postJson('/api/inputtemuan', [
        'kode_unit' => '4U01',
        'temuan' => $judulTemuan,
        'bidang_id' => $bidang->id,
        'rekomendasi' => [
            'Lengkapi dokumen pendukung tindak lanjut.',
        ],
    ])
        ->assertOk()
        ->assertJson(['success' => true]);

    $temuan = Temuan::with('rekomendasi')
        ->where('temuan', $judulTemuan)
        ->firstOrFail();
    $rekomendasi = $temuan->rekomendasi->first();

    expect($temuan->created_by)->toBe($spi->nik);

    $this->postJson('/api/kirimtemuan', [
        'temuan_id' => $temuan->id,
    ])
        ->assertOk()
        ->assertJson(['success' => true]);

    $logout();
    $loginAsNik('19990003');

    $this->post('/api/inputtindaklanjut', [
        'rekomendasi' => json_encode([
            [
                'id' => $rekomendasi->id,
                'temuan_id' => $temuan->id,
                'tindak_lanjut' => 'Dokumen pendukung sudah dilengkapi.',
            ],
        ], JSON_THROW_ON_ERROR),
        'bukti' => [
            UploadedFile::fake()->create('bukti-tindak-lanjut.pdf', 100, 'application/pdf'),
        ],
    ])
        ->assertOk()
        ->assertJson(['success' => true]);

    $rekomendasi->refresh();
    Storage::disk('local')->assertExists($rekomendasi->bukti);

    $logout();
    $loginAsNik('19990001');

    $this->postJson('/api/validasitemuan', [
        'rekomendasi' => [
            [
                'id' => $rekomendasi->id,
                'temuan_id' => $temuan->id,
                'tindak_lanjut' => $rekomendasi->tindak_lanjut,
                'status' => 'Sesuai',
                'alasan' => 'Bukti sesuai dengan rekomendasi.',
            ],
        ],
    ])
        ->assertOk()
        ->assertJson(['success' => true]);

    $logout();
    $loginAsNik('19990003');

    $this->postJson('/api/unitcekvalidasi', [
        'temuan_id' => $temuan->id,
        'rekomendasi' => [
            ['id' => $rekomendasi->id],
        ],
    ])
        ->assertOk()
        ->assertJson(['success' => true]);

    $temuan->refresh();
    expect($temuan->status)->toBe('Selesai');

    $this->postJson('/api/gettemuan')
        ->assertOk()
        ->assertJsonFragment([
            'temuan' => $judulTemuan,
            'status' => 'Selesai',
        ]);
});

test('bukti download is blocked for users outside the related unit or bagian', function () {
    $relatedBagianUser = User::where('nik', '19990007')->firstOrFail();
    $otherBagianUser = User::where('nik', '19990006')->firstOrFail();
    $otherUnitUser = User::where('nik', '19990004')->firstOrFail();
    $spi = User::where('nik', '19990001')->firstOrFail();

    $rekomendasi = Rekomendasi::whereNotNull('bukti')
        ->whereHas('temuan', fn ($query) => $query
            ->where('kode_unit', '4R00')
            ->where('kode_bagian', '4SDM'))
        ->firstOrFail();

    $this->actingAs($otherBagianUser)
        ->get(route('bukti.show', $rekomendasi))
        ->assertForbidden();

    $this->actingAs($otherUnitUser)
        ->get(route('bukti.show', $rekomendasi))
        ->assertForbidden();

    $this->actingAs($relatedBagianUser)
        ->get(route('bukti.show', $rekomendasi))
        ->assertOk();

    $this->actingAs($spi)
        ->get(route('bukti.show', $rekomendasi))
        ->assertOk();
});
