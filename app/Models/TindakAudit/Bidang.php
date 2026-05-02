<?php

namespace App\Models\TindakAudit;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bidang extends Model
{
    use HasFactory;

    protected $connection = 'pgsql';

    protected $table = 'tindakaudit.bidang';

    protected $fillable = [
        'nama',
    ];
}
