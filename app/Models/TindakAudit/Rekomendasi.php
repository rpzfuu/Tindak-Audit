<?php

namespace App\Models\TindakAudit;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rekomendasi extends Model
{
    use HasFactory;

    protected $connection = 'pgsql';

    protected $table = 'tindakaudit.rekomendasi';

    protected $appends = [
        'bukti_url',
    ];

    protected $fillable = [
        'temuan_id',
        'rekomendasi',
        'status',
        'alasan',
        'created_at',
        'updated_at',
        'tindak_lanjut',
        'bukti',
    ];

    public function temuan()
    {
        return $this->belongsTo(Temuan::class, 'temuan_id', 'id');
    }

    public function rekomendasi_history()
    {
        return $this->hasMany(RekomendasiHistory::class, 'rekomendasi_id', 'id');
    }

    public function getBuktiUrlAttribute(): ?string
    {
        if (empty($this->bukti)) {
            return null;
        }

        return route('bukti.show', $this->id);
    }
}
