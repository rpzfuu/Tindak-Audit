<?php

namespace App\Models\HRIS;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubBagian extends Model
{
    use HasFactory;

    protected $connection = 'superapps';

    protected $table = 'hris.sub_bagian';

    public function bagian()
    {
        return $this->hasOne(Bagian::class, 'code', 'bagian_code');
    }
}
