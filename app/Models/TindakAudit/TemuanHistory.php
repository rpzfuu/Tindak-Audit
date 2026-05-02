<?php

namespace App\Models\TindakAudit;

use App\Models\HRIS\Bagian;
use App\Models\HRIS\Karyawan;
use App\Models\HRIS\UnitUsaha;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TemuanHistory extends Model
{
    use HasFactory;

    protected $connection = 'pgsql';

    protected $table = 'tindakaudit.temuan_history';

    protected $fillable = [
        'temuan_id',
        'temuan',
        'kode_unit',
        'created_at',
        'updated_at',
        'bidang_id',
        'kode_bagian',
        'status',
        'changed_by',
        'keterangan',
        'action',
    ];

    public function temuan()
    {
        return $this->hasOne(Temuan::class, 'id', 'temuan_id');
    }

    public function rekomendasi_history()
    {
        return $this->hasMany(RekomendasiHistory::class, 'temuan_history_id', 'id');
    }

    public function bidang()
    {
        return $this->hasOne(Bidang::class, 'id', 'bidang_id');
    }

    public function karyawan()
    {
        return $this->hasOne(Karyawan::class, 'nik', 'changed_by');
    }

    public function unit_usaha()
    {
        return $this->hasOne(UnitUsaha::class, 'kode_unit', 'kode_unit');
    }

    public function bagian()
    {
        return $this->hasOne(Bagian::class, 'code', 'kode_bagian');
    }
}
