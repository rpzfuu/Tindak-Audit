<?php

namespace App\Models\TindakAudit;

use App\Models\HRIS\Bagian;
use App\Models\HRIS\UnitUsaha;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notifikasi extends Model
{
    use HasFactory;

    protected $connection = 'pgsql';

    protected $table = 'tindakaudit.notifikasi';

    protected $fillable = [
        'kode_unit',
        'kode_bagian',
        'action',
        'temuan_id',
        'created_at',
        'updated_at',
        'read',
        'message',
    ];

    public function temuan()
    {
        return $this->hasOne(Temuan::class, 'id', 'temuan_id');
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
