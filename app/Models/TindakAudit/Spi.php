<?php

namespace App\Models\TindakAudit;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Spi extends Model
{
    use HasFactory;

    protected $connection = 'pgsql';

    protected $table = 'tindakaudit.spi';

    public $timestamps = false;

    protected $fillable = [
        'nik',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'nik', 'nik');
    }
}
