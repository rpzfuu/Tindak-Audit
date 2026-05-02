<?php

namespace App\Models\TindakAudit;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RekomendasiHistory extends Model
{
    use HasFactory;

    protected $connection = 'pgsql';

    protected $table = 'tindakaudit.rekomendasi_history';

    protected $fillable = [
        'temuan_history_id',
        'rekomendasi',
        'status',
        'alasan',
        'created_at',
        'updated_at',
        'rekomendasi_id',
        'tindak_lanjut',
        'action',
    ];

    public function temuan_history()
    {
        return $this->belongsTo(TemuanHistory::class, 'temuan_history_id', 'id');
    }
}
